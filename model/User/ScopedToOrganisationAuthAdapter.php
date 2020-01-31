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

namespace oat\taoSync\model\User;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\TaoOntology;
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoTestCenter\model\EligibilityService;

class ScopedToOrganisationAuthAdapter extends HandShakeAuthAdapter
{
    use OntologyAwareTrait;

    /**
     * Try to authenticate an user scoped to an organisation
     *
     * Call the handshake to retrieve the user from remote server
     * Check if the user is a testaker and also check if one of associated testcenters have the same organisation id of last synchronisation
     *
     * @return \common_user_User
     * @throws \core_kernel_users_InvalidLoginException
     * @throws \core_kernel_persistence_Exception
     */
    public function authenticate()
    {
        $user = parent::authenticate();
        if (!$this->isTestTakerAllowed($this->getResource($user->getIdentifier()))) {
            $this->logNotice(sprintf('This user is limited to another organisation (%s)', $user->getIdentifier()));
            throw new \core_kernel_users_InvalidLoginException();
        }
        return $user;
    }

    /**
     * CHeck if the given user is a testtaker and he is part of last synchronisation
     * @param \core_kernel_classes_Resource $testtaker
     * @return bool
     * @throws \core_kernel_persistence_Exception
     */
    protected function isTestTakerAllowed(\core_kernel_classes_Resource $testtaker)
    {
        if (!$testtaker->isInstanceOf($this->getClass(TaoOntology::CLASS_URI_SUBJECT))) {
            return true;
        }

        $eligibilities = ServiceManager::getServiceManager()->get(EligibilityService::SERVICE_ID)->getEligibilityByTestTaker($testtaker);

        $testtakerOrganisanisationId = [];
        /** @var \core_kernel_classes_Resource $eligibility */
        foreach ($eligibilities as $eligibility) {
            $testCenter = $eligibility->getOnePropertyValue($this->getProperty(EligibilityService::PROPERTY_TESTCENTER_URI));
            if ($testCenter instanceof \core_kernel_classes_Resource && $testCenter->exists()) {
                $organisationId = $testCenter->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));
                $testtakerOrganisanisationId[] = $organisationId instanceof \core_kernel_classes_Literal
                    ? $organisationId->literal
                    : (string)$organisationId;
            }
        }

        $orgId = $this->getResource(DataSyncHistoryService::SYNCHRO_URI)
            ->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));
        $orgId = is_null($orgId) ? 0 : (int)$orgId->literal;

        return in_array($orgId, $testtakerOrganisanisationId);
    }
}
