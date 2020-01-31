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

namespace oat\taoSync\model\synchronizer\user;

use oat\tao\model\TaoOntology;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\generis\model\GenerisRdf;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\search\base\exception\SearchGateWayExeption;
use oat\search\helper\SupportedOperatorHelper;
use oat\taoSync\model\Entity;

abstract class UserSynchronizer extends AbstractResourceSynchronizer
{
    /**
     * Get the role defining which kind of user it is
     *
     * @return string
     */
    abstract protected function getUserRole();

    /**
     * Get the root class of entity to synchronize
     *
     * @return \core_kernel_classes_Class
     */
    protected function getRootClass()
    {
        return $this->getClass(TaoOntology::CLASS_URI_TAO_USER);
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
        $query->add(GenerisRdf::PROPERTY_USER_ROLES)->equals($this->getUserRole());

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
     * List of class to not synchronize
     *
     * @return array
     */
    protected function getExcludedClasses()
    {
        $excludedClasses = [
            TaoOntology::CLASS_URI_TAO_USER,
            GenerisRdf::CLASS_GENERIS_USER
        ];
        return array_merge(parent::getExcludedClasses(), $excludedClasses);
    }
}
