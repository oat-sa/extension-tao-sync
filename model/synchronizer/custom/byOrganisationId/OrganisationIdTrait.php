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

namespace oat\taoSync\model\synchronizer\custom\byOrganisationId;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\search\base\exception\SearchGateWayExeption;
use oat\taoSync\model\Entity;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

trait OrganisationIdTrait
{
    abstract public function getServiceLocator();

    /**
     * Extract the organisation id parameter from $parameters
     *
     * @param array $options
     * @return string
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     */
    protected function getOrganisationIdFromOption(array $options = [])
    {
        if (!isset($options[TestCenterByOrganisationId::OPTION_ORGANISATION_ID])) {
            $ids = \common_session_SessionManager::getSession()->getUserPropertyValues(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);
            $id = reset($ids);
            if (empty($id)) {
                $this->logError('Organisation id cannot be retrieved from parameters. Current synchronisation aborted.');
                throw new \common_exception_NotFound();
            }
            $options[TestCenterByOrganisationId::OPTION_ORGANISATION_ID] = $id;
        }
        return (string) $options[TestCenterByOrganisationId::OPTION_ORGANISATION_ID];
    }

    /**
     * Get all testcenters associated to the given $id
     *
     * @param $orgId
     * @return array
     */
    protected function getTestCentersByOrganisationId($orgId)
    {
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, TestCenterService::CLASS_URI, true);
        $query->add(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY)->equals($orgId);
        $queryBuilder->setCriteria($query);

        try {
            $results = $search->getGateway()->search($queryBuilder);
            $values = [];
            if ($results->total() > 0) {
                foreach ($results as $resource) {
                    $values[$resource->getUri()] = $resource;
                }
            }
            return $values;
        } catch (SearchGateWayExeption $e) {
            $this->logError('SQL error during processiong of ' . __METHOD__ . ' : ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all eligibilities associated to a test center with given $id
     *
     * @param $orgId
     * @return \core_kernel_classes_Resource[]
     */
    protected function getEligibilitiesByOrganisationId($orgId)
    {
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, EligibilityService::CLASS_URI, true);
        $queryBuilder->setCriteria($query);

        $queryBuilder2 = $search->query();
        $query2 = $search->searchType($queryBuilder2, TestCenterService::CLASS_URI, true);
        $query2->add(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY)->equals($orgId);
        $queryBuilder2->setCriteria($query2);

        /** @var QueryJoiner $joiner */
        $joiner = $search->getGateway()->getJoiner();
        $joiner->setQuery($queryBuilder)
            ->join($queryBuilder2)
            ->on(EligibilityService::PROPERTY_TESTCENTER_URI);

        $results = $search->getGateway()->join($joiner);

        $values = [];
        if ($results->total() > 0) {
            foreach ($results as $resource) {
                $values[$resource->getUri()] = $resource;
            }
        }

        return $values;
    }

    /**
     * Post apply options in $params to set of resources
     *
     * @param $resources
     * @param $params
     * @return array
     * @throws \common_Exception
     */
    protected function postApplyQueryOptions($resources, $params)
    {
        $sortedInstances = $values = [];
        $withProperties = isset($params['withProperties']) && (int) $params['withProperties'] == 1;
        /** @var \core_kernel_classes_Resource $resource */
        foreach ($resources as $resource) {
            if (!$resource->exists()) {
                continue;
            }
            $createdAt = $resource->getUniquePropertyValue($this->getProperty(Entity::CREATED_AT))->literal;
            $sortedInstances[$createdAt] = $this->format($resource, $withProperties, $params);
        }

        ksort($sortedInstances);

        $startCreatedAt = isset($params['startCreatedAt']) ? $params['startCreatedAt'] : false;
        $limit = isset($params['limit']) ? $params['limit'] : false;
        $offset = isset($params['offset']) ? $params['offset'] : 0;

        $current = 1;
        foreach ($sortedInstances as $createdAt => $instance) {
            if ($startCreatedAt !== false && $createdAt < $startCreatedAt) {
                continue;
            }
            if ($current < $offset) {
                continue;
            }

            $values[$instance['id']] = $instance;

            if ($limit !== false && ($current - $offset) == $limit) {
                break;
            }
            $current++;
        }

        return $values;
    }
}
