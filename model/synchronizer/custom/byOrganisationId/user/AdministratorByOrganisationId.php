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

use oat\generis\model\GenerisRdf;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\search\base\exception\SearchGateWayExeption;
use oat\search\helper\SupportedOperatorHelper;
use oat\taoSync\model\Entity;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\user\administrator\RdfAdministratorSynchronizer;
use oat\taoTestCenter\model\ProctorManagementService;

class AdministratorByOrganisationId extends RdfAdministratorSynchronizer
{
    use OrganisationIdTrait;

    /**
     * Get a list of administrators
     *
     * Scope it to test center organisation id
     *
     * @param array $params
     * @return array
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     */
    public function fetch(array $params = [])
    {
        $orgId = $this->getOrganisationIdFromOption($params);
        $testcenters = $this->getTestCentersByOrganisationId($orgId);

        $administratorResources = [];
        /** @var \core_kernel_classes_Resource $eligibility */
        foreach ($testcenters as $testcenter) {
            $administrators = $this->getAdministratorsByTestCenter($testcenter, $params);
            foreach ($administrators as $administrator) {
                $administratorResources[$administrator->getUri()] = $administrator;
            }
        }

        return $this->postApplyQueryOptions($administratorResources, $params);
    }

    /**
     * Get administrator associated to given $testcenter
     *
     * Fetch only testcenter administrator role
     *
     * @param \core_kernel_classes_Resource $testcenter
     * @param $params
     * @return array
     */
    protected function getAdministratorsByTestCenter(\core_kernel_classes_Resource $testcenter, $params)
    {
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, $this->getRootClass()->getUri() , true);
        $query->add(GenerisRdf::PROPERTY_USER_ROLES)->equals($this->getUserRole());
        $query->add(ProctorManagementService::PROPERTY_ADMINISTRATOR_URI)->equals($testcenter->getUri());
        if (isset($params['startCreatedAt'])) {
            $query->addCriterion(Entity::CREATED_AT, SupportedOperatorHelper::GREATER_THAN_EQUAL, $params['startCreatedAt']);
        }
        $queryBuilder->setCriteria($query);

        try {
            $values = [];
            $results = $search->getGateway()->search($queryBuilder);
            foreach ($results as $result) {
                $values[] = $this->getResource($result);
            }
            return $values;
        } catch (SearchGateWayExeption $e) {
            $this->logError('SQL error during processiong of ' . __METHOD__ . ' : ' . $e->getMessage());
            return [];
        }

    }

}