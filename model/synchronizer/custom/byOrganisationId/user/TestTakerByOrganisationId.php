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

use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\user\testtaker\RdfTestTakerSynchronizer;
use oat\taoTestCenter\model\EligibilityService;

class TestTakerByOrganisationId extends RdfTestTakerSynchronizer
{
    use OrganisationIdTrait;

    /**
     * Get a list of testtakers
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
        $eligibilities = $this->getEligibilitiesByOrganisationId($orgId);

        $testtakerResources = [];
        /** @var \core_kernel_classes_Resource $eligibility */
        foreach ($eligibilities as $eligibility) {
            $testtakers = $eligibility->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_TESTTAKER_URI));
            foreach ($testtakers as $testtaker) {
                $testtakerResource = $this->getResource($testtaker);
                $testtakerResources[$testtakerResource->getUri()] = $testtakerResource;
            }
        }

        return $this->postApplyQueryOptions($testtakerResources, $params);
    }

}