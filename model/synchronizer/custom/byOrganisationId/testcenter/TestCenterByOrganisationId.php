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
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\testcenter\RdfTestCenterSynchronizer;
use oat\taoTestCenter\model\TestCenterService;

class TestCenterByOrganisationId extends RdfTestCenterSynchronizer
{
    use OrganisationIdTrait;

    const ORGANISATION_ID_PROPERTY = 'http://www.taotesting.com/ontologies/synchro.rdf#organisationId';
    const OPTION_ORGANISATION_ID = 'organisationId';

    public function fetchOne($id, array $options = [])
    {
        $resource = $this->getResource($id);
        $orgId = $this->getOrganisationIdFromOption($options);
        $orgIdPropertyValue = $resource->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));
        if ($orgIdPropertyValue != $orgId) {
            throw new \common_exception_NotFound('No resource found for id : ' . $id);
        }
        return $this->format($resource);
    }

    public function fetch(array $options = [])
    {
        $id = $this->getOrganisationIdFromOption($options);
        /**
         * @todo apply options
         */
        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, TestCenterService::CLASS_URI , true);
        $query->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id);
        $queryBuilder->setCriteria($query);
        $results = $search->getGateway()->search($queryBuilder);
        $values = [];
        if ($results->total() > 0) {
            /** @var \core_kernel_classes_Resource $resource */
            foreach ($results as $resource) {
                $instance = $this->format($resource);
                $values[$instance['id']] = $instance;
            }
        }
        return $values;
    }

}