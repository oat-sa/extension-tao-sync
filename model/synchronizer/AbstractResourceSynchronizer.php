<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model\synchronizer;

use oat\generis\model\GenerisRdf;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\service\ConfigurableService;
use oat\search\base\exception\SearchGateWayExeption;
use oat\search\base\QueryBuilderInterface;
use oat\search\helper\SupportedOperatorHelper;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\Entity;
use oat\taoSync\model\formatter\FormatterService;
use oat\taoSync\model\formatter\SynchronizerFormatter;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Class AbstractResourceSynchronizer
 *
 * An abstract class to help to synchronize resource properties
 *
 * Allow fetch, fetchOne, insert/update/delete multiple resource
 * Format method he checksum
 * Manage the resource class tree and synchronize remote missing class
 *
 * @package oat\taoSync\model\synchronizer
 */
abstract class AbstractResourceSynchronizer extends ConfigurableService implements ServiceLocatorAwareInterface, RdfClassSynchronizer
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;

    const OPTIONS_EXCLUDED_FIELDS = 'excludedProperties';
    const OPTIONS_FIELDS = 'fields';
    const OPTIONS_FORMATTER_CLASS = 'formatter';

    /**
     * Get the root class of entity to synchronize
     *
     * @return \core_kernel_classes_Class
     */
    abstract protected function getRootClass();

    /**
     * This method is call before to write the data to synchronize
     *
     * @param array $entities
     */
    public function before(array $entities)
    {
        $requestedClasses = [];
        foreach (array_merge($entities['create'], $entities['update']) as $entity) {
            if (isset($entity['properties'][OntologyRdf::RDF_TYPE]) && $entity['properties'][OntologyRdf::RDF_TYPE]) {
                $type = $entity['properties'][OntologyRdf::RDF_TYPE];
                if (!$this->getClass($type)->exists()) {
                    $requestedClasses[] = $type;
                }
            }
        }

        if (!empty($requestedClasses)) {
            $requestedClasses = array_unique($requestedClasses);
            $missingClasses = $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID)->getMissingClasses($this->getId(), $requestedClasses);
            foreach ($missingClasses as $remoteEntity) {
                try {
                    $this->createClassRecursively($remoteEntity['id'], $remoteEntity['properties'], $missingClasses);
                } catch (\common_Exception $e) {
                }
            }
        }
    }

    /**
     * This method is call after synchronization process
     *
     * @param array $entities
     */
    public function after(array $entities)
    {
    }

    /**
     * Get the requested class triples with associated tree
     *
     * @param $requestedClasses
     * @return array
     */
    public function fetchMissingClasses($requestedClasses)
    {
        $classes = [];
        foreach ($requestedClasses as $classUri) {
            $class = $this->getClass($classUri);
            /** @var \core_kernel_classes_Class $parent */
            foreach ($class->getParentClasses(true) as $parent) {
                if (!in_array($parent->getUri(), array_keys($classes)) && !in_array($parent->getUri(), $this->getExcludedClasses())) {
                    $classes[$parent->getUri()] = $this->format($parent, true);
                }
            }
            if (!in_array($class->getUri(), array_keys($classes)) && !in_array($class->getUri(), $this->getExcludedClasses())) {
                $classes[$class->getUri()] = $this->format($class, true);
            }
        }
        return $classes;
    }

    /**
     * Get a list of entities
     *
     * @param array $params
     * @return array
     */
    public function fetch(array $params = [])
    {
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();

        $query = $search->searchType($queryBuilder, $this->getRootClass()->getUri(), true);

        if (isset($params['startCreatedAt'])) {
            $query->addCriterion(Entity::CREATED_AT, SupportedOperatorHelper::GREATER_THAN_EQUAL, $params['startCreatedAt']);
        }

        $queryBuilder->setCriteria($query);
        $this->applyQueryOptions($queryBuilder, $params);

        $values = [];

        try {
            $results = $search->getGateway()->search($queryBuilder);
            if ($results->total() > 0) {
                $withProperties = isset($params['withProperties']) && (int) $params['withProperties'] == 1;
                foreach ($results as $resource) {
                    $instance = $this->format($resource, $withProperties, $params);
                    $values[$instance['id']] = $instance;
                }
            }
        } catch (SearchGateWayExeption $e) {
        }

        return $values;
    }

    /**
     * Fetch an entity associated to the given id in Rdf storage
     *
     * @param $id
     * @param array $params
     * @return array
     * @throws \common_exception_NotFound If entity is not found
     */
    public function fetchOne($id, array $params = [])
    {
        $withProperties = isset($params['withProperties']) && (int) $params['withProperties'] == 1;

        $resource = $this->getResource($id);
        if (!$resource->exists()) {
            throw new \common_exception_NotFound('No resource found for id : ' . $id);
        }

        return $this->format($resource, $withProperties, $params);
    }

    /**
     * Delete multiple entities to Rdf storage
     *
     * @param array $entityIds
     */
    public function deleteMultiple(array $entityIds)
    {
        foreach ($entityIds as $id) {
            $this->getResource($id)->delete();
        }
    }

    /**
     * Insert multiple entities to Rdf storage
     *
     * @param array $entities
     * @return array
     */
    public function insertMultiple(array $entities)
    {
        $created = [];
        $excludedFields = $this->getOption(self::OPTIONS_EXCLUDED_FIELDS) ?: [];
        foreach ($entities as $entity) {
            $properties = isset($entity['properties']) ? $entity['properties'] : [];
            if (isset($properties[OntologyRdf::RDF_TYPE])) {
                $class = $this->getClass($properties[OntologyRdf::RDF_TYPE]);
            } else {
                $class = $this->getRootClass();
            }

            $resource = $this->getResource($entity['id']);
            $resource->setType($class);

            foreach ($properties as $property => $value) {
                if (in_array($property, $excludedFields)) {
                    unset($properties[$property]);
                }
            }

            $resource->setPropertiesValues($properties);

            $created[] = $entity['id'];
        }

        return $created;
    }

    /**
     * Update multiple entities to Rdf storage
     *
     * @param array $entities
     */
    public function updateMultiple(array $entities)
    {
        foreach ($entities as $entity) {
            $properties = isset($entity['properties']) ? $entity['properties'] : [];
            $resource = $this->getResource($entity['id']);
            $triples = $resource->getRdfTriples();
            foreach ($triples as $triple) {
                $resource->removePropertyValues($this->getProperty($triple->predicate));
            }
            $resource->setPropertiesValues($properties);
        }
    }

    /**
     * Format a resource to an array
     *
     * Add a checksum to identify the resource content
     * Add resource triples as properties if $withProperties param is true
     *
     * @param \core_kernel_classes_Resource $resource
     * @param boolean $withProperties
     * @param array $params
     * @return array
     */
    public function format(\core_kernel_classes_Resource $resource, $withProperties = false, array $params = [])
    {
        $options = [
            FormatterService::OPTION_ONLY_FIELDS => is_array($this->getOption(self::OPTIONS_FIELDS))
                ? $this->getOption(self::OPTIONS_FIELDS)
                : [],
            FormatterService::OPTION_EXCLUDED_FIELDS => is_array($this->getOption(self::OPTIONS_EXCLUDED_FIELDS))
                ? $this->getOption(self::OPTIONS_EXCLUDED_FIELDS)
                : [],
            FormatterService::OPTION_INCLUDED_PROPERTIES => $withProperties
        ];

        return $this->getFormatter()->format($resource, $options, $params);
    }

    /**
     * Get value of entity property
     *
     * @param $id
     * @param $property
     * @return string
     * @throws \core_kernel_persistence_Exception
     */
    public function getEntityProperty($id, $property)
    {
        return (string) $this->getResource($id)->getOnePropertyValue($this->getProperty($property));
    }

    /**
     * Create a class and the tree of his parents
     *
     * @param $id
     * @param array $params
     * @param array $missingClasses
     * @throws \common_exception_NotFound If parent class does not exist
     */
    protected function createClassRecursively($id, array $params, array &$missingClasses = [])
    {
        $parent = $this->getClass($params[OntologyRdfs::RDFS_SUBCLASSOF]);
        if ($this->getRootClass()->getUri() != $parent->getUri() && !$parent->exists()) {
            if (!in_array($parent->getUri(), array_keys($missingClasses))) {
                throw new \common_exception_NotFound('Class "' . $id . '" cannot be created, parent "' . $parent->getUri() . '" does not exists.');
            }
            $this->createClassRecursively($parent->getUri(), $missingClasses[$parent->getUri()]['properties'], $missingClasses);
        }

        $label = isset($params[OntologyRdfs::RDFS_LABEL]) ? $params[OntologyRdfs::RDFS_LABEL] : null;
        $comment = isset($params[OntologyRdfs::RDFS_COMMENT]) ? $params[OntologyRdfs::RDFS_COMMENT] : null;
        $parent->createSubClass($label, $comment, $id);
        unset($missingClasses[$id]);
    }

    /**
     * Update the query based on $param
     *
     * @param QueryBuilderInterface $queryBuilder
     * @param array $params
     */
    protected function applyQueryOptions(QueryBuilderInterface $queryBuilder, array $params = [])
    {
        if (isset($params['limit'])) {
            $queryBuilder->setLimit($params['limit']);
        }
        if (isset($params['offset'])) {
            $queryBuilder->setOffset($params['offset']);
        }
        if (isset($params['order']) && is_array($params['order'])) {
            $sorting = [];
            foreach ($params['order'] as $sort => $order) {
                if (in_array($order, ['desc','asc'])) {
                    $sorting[$sort] = $order;
                }
            }
            if (!empty($sorting)) {
                $queryBuilder->sort($sorting);
            }
        }
    }

    /**
     * List of class to not synchronize
     *
     * @return array
     */
    protected function getExcludedClasses()
    {
        return [
            GenerisRdf::CLASS_GENERIS_RESOURCE,
            OntologyRdfs::RDFS_RESOURCE,
            $this->getRootClass()->getUri(),
        ];
    }

    /**
     * Return formatter from current synchronizer options
     * Otherwise return the default FormatterService
     *
     * @return FormatterService
     */
    public function getFormatter()
    {
        if ($this->hasOption(self::OPTIONS_FORMATTER_CLASS)) {
            $formatterClass = $this->getOption(self::OPTIONS_FORMATTER_CLASS);
            if (is_a($formatterClass, SynchronizerFormatter::class, true)) {
                return new $formatterClass();
            } elseif ($this->getServiceLocator()->has($this->getOption(self::OPTIONS_FORMATTER_CLASS))) {
                return $this->getServiceLocator()->get($this->getOption(self::OPTIONS_FORMATTER_CLASS));
            }
        }
        return new FormatterService();
    }
}
