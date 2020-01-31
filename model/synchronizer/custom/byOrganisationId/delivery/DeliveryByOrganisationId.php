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

    /**
     * Get a list of delivery
     *
     * Scope it to eligibilities associated to test center organisation id
     *
     * @param array $params
     * @return array
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     */
    public function fetch(array $params = [])
    {
        $orgId = $this->getOrganisationIdFromOption($params);
        $eligibilities = $this->getEligibilitiesByOrganisationId($orgId);

        $deliveryResources = [];
        /** @var \core_kernel_classes_Resource $eligibility */
        foreach ($eligibilities as $eligibility) {
            $deliveries = $eligibility->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_DELIVERY_URI));
            foreach ($deliveries as $delivery) {
                $deliveryResource = $this->getResource($delivery);
                $deliveryResources[$deliveryResource->getUri()] = $deliveryResource;
            }
        }

        return $this->postApplyQueryOptions($deliveryResources, $params);
    }
}
