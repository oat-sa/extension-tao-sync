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

namespace oat\taoSync\model\synchronizer\custom\byOrganisationId\delivery;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\synchronizer\delivery\RdfDeliverySynchronizer;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

class DeliveryByOrganisationId extends RdfDeliverySynchronizer
{
    use OrganisationIdTrait;

    public function fetch(array $options = [])
    {
        $id = $this->getOrganisationIdFromOption($options);

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, EligibilityService::CLASS_URI, true);
        $queryBuilder->setCriteria($query);

        $queryBuilder2 = $search->query();
        $query2 = $search->searchType($queryBuilder2, TestCenterService::CLASS_URI, true)
            ->add(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY)->equals($id);
        $queryBuilder2->setCriteria($query2);

        /** @var QueryJoiner $joiner */
        $joiner = $search->getGateway()->getJoiner();
        $joiner->setQuery($queryBuilder)
            ->join($queryBuilder2)
            ->on(EligibilityService::PROPERTY_TESTCENTER_URI);

        $results = $search->getGateway()->join($joiner);
        $values = [];
        if ($results->total() > 0) {
            /** @var \core_kernel_classes_Resource $resource */
            foreach ($results as $resource) {
                $deliveryUris = $resource->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_DELIVERY_URI));
                foreach ($deliveryUris as $deliveryUri) {
                    $delivery = $this->getResource($deliveryUri);
                    if ($delivery->exists()) {
                        $instance = $this->format($delivery);
                        $values[$instance['id']] = $instance;
                    }
                }
            }
        }

        return $values;
    }

}