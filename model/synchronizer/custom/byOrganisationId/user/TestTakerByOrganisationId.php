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

namespace oat\taoSync\model\synchronizer\custom\byOrganisationId\user;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\search\helper\SupportedOperatorHelper;
use oat\taoSync\model\Entity;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\synchronizer\user\testtaker\RdfTestTakerSynchronizer;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

class TestTakerByOrganisationId extends RdfTestTakerSynchronizer
{
    use OrganisationIdTrait;

    protected function getEligibilities($params)
    {
        $orgId = $this->getOrganisationIdFromOption($params);

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
     * Get a list of entities
     *
     * Scope it to test center organisation id
     *
     * @param array $params
     * @return array
     */
    public function fetch(array $params = [])
    {
        $eligibilities = $this->getEligibilities($params);
        $values = $sortedInstances = [];

        /** @var \core_kernel_classes_Resource $eligibility */
        foreach ($eligibilities as $eligibility) {
            foreach ($eligibility->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_TESTTAKER_URI)) as $testtaker) {
                $instance = $this->format($this->getResource($testtaker), true);
                $sortedInstances[$instance['properties'][Entity::CREATED_AT]] = $instance;
            }
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