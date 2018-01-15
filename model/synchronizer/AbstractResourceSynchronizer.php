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
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\Configurable;
use oat\taoSync\model\api\SynchronisationClient;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractResourceSynchronizer extends Configurable implements ServiceLocatorAwareInterface, Synchronizer
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;

    const OPTIONS_EXCLUDED_FIELDS = 'excludedProperties';
    const OPTIONS_FIELDS = 'fields';

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
     * Create a class and the tree of his parents
     *
     * @param $id
     * @param array $params
     * @param array $missingClasses
     * @throws \common_exception_NotFound If parent class does not exist
     */
    protected function createClassRecursively($id, array $params,  array &$missingClasses = [])
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
                    $classes[$parent->getUri()] = $this->format($parent);
                }
            }
            if (!in_array($class->getUri(), array_keys($classes)) && !in_array($class->getUri(), $this->getExcludedClasses())) {
                $classes[$class->getUri()] = $this->format($class);
            }
        }
        return $classes;
    }

    /**
     * Get a list of instances
     *
     * @param array $options
     * @return array
     */
    public function fetch(array $options = [])
    {
        $resources = $this->getRootClass()->getInstances(true, $this->getDefaultOptions());
        $values = [];
        /** @var \core_kernel_classes_Resource $resource */
        foreach ($resources as $resource) {
            $instance = $this->format($resource);
            $values[$instance['id']] = $instance;
        }

        return $values;
    }

    /**
     * Fetch an entity associated to the given id in Rdf storage
     *
     * @param $id
     * @return array
     * @throws \common_exception_NotFound If entity is not found
     */
    public function fetchOne($id)
    {
        $resource = $this->getResource($id);
        if (!$resource->exists()) {
            throw new \common_exception_NotFound('No resource found for id : ' . $id);
        }
        return $this->format($resource);
    }

    /**
     * Return count of instances
     *
     * @return int
     */
    public function count()
    {
        return $this->getRootClass()->countInstances([], ['recursive' => true]);
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
     */
    public function insertMultiple(array $entities)
    {
        foreach ($entities as $entity) {
            $properties = isset($entity['properties']) ? $entity['properties'] : [];
            if (isset($properties[OntologyRdf::RDF_TYPE])) {
                $class = $this->getClass($properties[OntologyRdf::RDF_TYPE]);
            } else {
                $class = $this->getRootClass();
            }

            $label = isset($properties[OntologyRdfs::RDFS_LABEL]) ? $properties[OntologyRdfs::RDFS_LABEL] : null;
            $comment = isset($properties[OntologyRdfs::RDFS_COMMENT]) ? $properties[OntologyRdfs::RDFS_COMMENT] : null;
            $resource = $class->createInstance($label, $comment, $entity['id']);
            $resource->setPropertiesValues($properties);
        }
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

    protected function getDefaultOptions()
    {
        return [
            'limit' => 100,
            'offset' => 0,
            'orderdir' => 'asc'
        ];
    }

    /**
     * Format a resource to an array
     *
     * Add a checksum to identify the resource content
     * Add resource triples as properties
     *
     * @param \core_kernel_classes_Resource $resource
     * @return array
     */
    protected function format(\core_kernel_classes_Resource $resource)
    {
        $properties = $this->filterProperties($resource->getRdfTriples()->toArray());
        $value = [];
        $value['id'] = $resource->getUri();
        $value['type'] = $resource->isClass() ? 'class' : 'resource';
        $value['checksum'] = md5(serialize($properties));
        $value['properties'] = $properties;
        return $value;
    }

    /**
     * Filter resource triples against the SyncService configuration
     *
     * self::OPTIONS_FIELDS, if it is set only these fields will be taken under account
     * self::OPTIONS_EXCLUDED_FIELDS, excluded fields
     *
     * return an array of $predicate => $object
     *
     * @param array $triples
     * @return array
     */
    protected function filterProperties(array $triples)
    {
        $fields = is_array($this->getOption(self::OPTIONS_FIELDS)) ? $this->getOption(self::OPTIONS_FIELDS) : [];
        $excludedFields = is_array($this->getOption(self::OPTIONS_EXCLUDED_FIELDS)) ? $this->getOption(self::OPTIONS_EXCLUDED_FIELDS) : [];

        $properties = [];

        /** @var \core_kernel_classes_Triple $triple */
        foreach ($triples as $triple) {
            if (!empty($fields) && in_array($triple->predicate, $fields)) {
                $properties[$triple->predicate] = $triple->object;
            } else {
                if (!in_array($triple->predicate, $excludedFields)) {
                    $properties[$triple->predicate] = $triple->object;
                }
            }
        }

        return $properties;
    }

    /**
     * List of class to not synchronize
     *
     * @return array
     */
    protected function getExcludedClasses()
    {
        return [
            'http://www.tao.lu/Ontologies/TAO.rdf#User',
            'http://www.tao.lu/Ontologies/generis.rdf#User',
            'http://www.tao.lu/Ontologies/generis.rdf#generis_Ressource',
            'http://www.w3.org/2000/01/rdf-schema#Resource',
            $this->getRootClass()->getUri(),
        ];
    }
}