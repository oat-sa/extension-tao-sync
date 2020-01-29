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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model\testCenter;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\SyncService;
use oat\taoSync\model\testCenter\Domain\TestCenter;

/**
 * Class SyncManagerTreeService
 * @package oat\taoSync\model\testCenter
 * @author Yury Filippovich, <yury.filipovich@1pt.com>
 */
class SyncManagerTreeService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/SyncManagerTreeService';

    /**
     * Save the values sent from the SyncUser form
     *
     * @param TestCenter $testCenter
     * @param \core_kernel_classes_Resource $user
     * @return bool
     */
    public function saveReversedValues(TestCenter $testCenter, \core_kernel_classes_Resource $user)
    {
        $assignedSyncUserProperty = $this->getProperty(SyncService::PROPERTY_ASSIGNED_SYNC_USER);
        $organisationIdProperty = $this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);

        $success = $this->unassignOthersUsersFromTestCenter($testCenter, $user);

        $user->removePropertyValues($assignedSyncUserProperty);
        $user->removePropertyValues($organisationIdProperty);

        $success = $success && $user->setPropertyValue($assignedSyncUserProperty, $testCenter->getTestCenter());
        $success = $success && $user->setPropertyValue($organisationIdProperty, $testCenter->getOrganisationId());

        return $success;
    }

    /**
     * @param TestCenter $testCenterDomain
     * @param \core_kernel_classes_Resource $user
     * @return bool
     */
    public function unassignOthersUsersFromTestCenter(TestCenter $testCenterDomain, \core_kernel_classes_Resource $user)
    {
        return $this->unassignUsersFromTestCenter(
            $testCenterDomain,
            array_filter(
                $this->getAssignedSyncManagers($testCenterDomain),
                function (\core_kernel_classes_Resource $existUser) use ($user) {
                    return $existUser->getUri() !== $user->getUri();
                }
            )
        );
    }

    /**
     * @param TestCenter $testCenterDomain
     * @return bool
     */
    public function unassignAllUsersFromTestCenter(TestCenter $testCenterDomain)
    {
        return $this->unassignUsersFromTestCenter($testCenterDomain, $this->getAssignedSyncManagers($testCenterDomain));
    }

    /**
     * @param TestCenter $testCenterDomain
     * @param \core_kernel_classes_Resource[]_Resource $user
     * @return bool
     */
    private function unassignUsersFromTestCenter(TestCenter $testCenterDomain, array $users)
    {
        $success = true;
        if ($users) {
            $assignedSyncUserProperty = $this->getProperty(SyncService::PROPERTY_ASSIGNED_SYNC_USER);
            $organisationIdProperty = $this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);

            foreach ($users as $user) {
                $success = $success
                    && $user->removePropertyValue($assignedSyncUserProperty, $testCenterDomain->getTestCenter());
                $success = $success
                    && $user->removePropertyValue($organisationIdProperty, $testCenterDomain->getOrganisationId());
            }
        }
        return $success;
    }

    /**
     * @param TestCenter $testCenterDomain
     * @return \core_kernel_classes_Resource[]
     */
    public function getAssignedSyncManagers(TestCenter $testCenterDomain)
    {
        /** @var \tao_models_classes_UserService $userService */
        $userService = $this->getServiceLocator()->get(\tao_models_classes_UserService::SERVICE_ID);
        return $userService->getAllUsers(
            [],
            [SyncService::PROPERTY_ASSIGNED_SYNC_USER => $testCenterDomain->getTestCenter()]
        );
    }

    /**
     * @param \core_kernel_classes_Resource $user
     * @return string[]
     */
    public function getUserTestCenters(\core_kernel_classes_Resource $user)
    {
        return $user->getPropertyValues($this->getProperty(SyncService::PROPERTY_ASSIGNED_SYNC_USER));
    }

    /**
     * @param string $testCenterUri
     * @return TestCenter
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     */
    public function createTestCenterDomain($testCenterUri)
    {
        /** @var TestCenterService $testCenterService */
        $testCenterService = $this->getServiceLocator()->get(TestCenterService::SERVICE_ID);
        $testCenter = $this->getResource($testCenterUri);

        return new TestCenter(
            $this->getResource($testCenterUri),
            $testCenterService->getTestCenterOrganisationId($testCenter)
        );
    }
}
