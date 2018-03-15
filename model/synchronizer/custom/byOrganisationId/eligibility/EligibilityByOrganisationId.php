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

namespace oat\taoSync\model\synchronizer\custom\byOrganisationId\eligibility;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\search\helper\SupportedOperatorHelper;
use oat\taoSync\model\Entity;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\synchronizer\eligibility\RdfEligibilitySynchronizer;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

class EligibilityByOrganisationId extends RdfEligibilitySynchronizer
{
    use OrganisationIdTrait;

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
        $testCenter = $this->getResource($resource->getOnePropertyValue($this->getProperty(EligibilityService::PROPERTY_TESTCENTER_URI)));
        $orgIdPropertyValue = $testCenter->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));

        if ($orgIdPropertyValue != $orgId) {
            throw new \common_exception_NotFound('No organisation resource found for id : ' . $id);
        }

        return $this->format($resource, $withProperties);
    }

    /**
     * Get a list of entities
     *
     * Scope it to test center organisation id
     *
     * @param array $params
     * @return array
     * @throws \common_exception_NotFound
     */
    public function fetch(array $params = [])
    {
        $orgId = $this->getOrganisationIdFromOption($params);

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, $this->getRootClass()->getUri() , true);
        if (isset($params['startCreatedAt'])) {
            $query->addCriterion(Entity::CREATED_AT, SupportedOperatorHelper::GREATER_THAN_EQUAL, $params['startCreatedAt']);
        }
        $queryBuilder->setCriteria($query);
        $this->applyQueryOptions($queryBuilder, $params);

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
                $instance = $this->format($resource);
                $values[$instance['id']] = $instance;
            }
        }

        return $values;
    }

}