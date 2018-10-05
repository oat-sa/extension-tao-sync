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

namespace oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\search\base\exception\SearchGateWayExeption;
use oat\search\helper\SupportedOperatorHelper;
use oat\taoSync\model\Entity;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\testcenter\RdfTestCenterSynchronizer;

class TestCenterByOrganisationId extends RdfTestCenterSynchronizer
{
    use OrganisationIdTrait;

    const ORGANISATION_ID_PROPERTY = 'http://www.taotesting.com/ontologies/synchro.rdf#organisationId';
    const OPTION_ORGANISATION_ID = 'organisation_id';

    /**
     * Fetch an entity associated to the given id in Rdf storage
     *
     * Scope it to test center organisation id
     *
     * @param $id
     * @param array $params
     * @return array
     * @throws \common_exception_NotFound
     * @throws \core_kernel_persistence_Exception
     */
    public function fetchOne($id, array $params = [])
    {
        $withProperties = isset($params['withProperties']) && (int) $params['withProperties'] == 1;

        $resource = $this->getResource($id);
        if (!$resource->exists()) {
            throw new \common_exception_NotFound('No resource found for id : ' . $id);
        }

        $orgId = $this->getOrganisationIdFromOption($params);
        $orgIdPropertyValue = $resource->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));
        if ($orgIdPropertyValue != $orgId) {
            throw new \common_exception_NotFound('No resource with organisation id found for id : ' . $id);
        }

        return $this->format($resource, $withProperties, $params);
    }

    /**
     * Get a list of testcenters
     *
     * Scope it to test center organisation id
     *
     * @param array $params
     * @return array
     * @throws \common_exception_NotFound
     */
    public function fetch(array $params = [])
    {
        $id = $this->getOrganisationIdFromOption($params);

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();

        $query = $search->searchType($queryBuilder, $this->getRootClass()->getUri() , true);
        $query->add(self::ORGANISATION_ID_PROPERTY)->equals($id);

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
        } catch (SearchGateWayExeption $e) {}

        return $values;
    }

}