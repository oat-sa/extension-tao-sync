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
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\user\administrator\RdfAdministratorSynchronizer;
use oat\taoTestCenter\model\ProctorManagementService;
use oat\taoTestCenter\model\TestCenterService;

class AdministratorByOrganisationId extends RdfAdministratorSynchronizer
{
    use OrganisationIdTrait;

    public function fetch(array $options = [])
    {
        $id = $this->getOrganisationIdFromOption($options);

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, $this->getRootClass()->getUri() , true);
        $queryBuilder->setCriteria($query);

        $queryBuilder2 = $search->query();
        $query2 = $search->searchType($queryBuilder2, TestCenterService::CLASS_URI, true);
        $query2->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id);
        $queryBuilder2->setCriteria($query2);

        /** @var QueryJoiner $joiner */
        $joiner = $search->getGateway()->getJoiner();
        $joiner->setQuery($queryBuilder)
            ->join($queryBuilder2)
            ->on(ProctorManagementService::PROPERTY_ADMINISTRATOR_URI);

//        $joiner->sort($sorting);
//
//        if (!is_null($offset)) {
//            $joiner->setOffset($offset);
//        }
//
//        if (!is_null($limit)) {
//            $joiner->setLimit($limit);
//        }
//
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