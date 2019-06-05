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
 *
 */
namespace oat\taoSync\model\testCenter;

use oat\taoTestCenter\model\TestCenterService as BaseTestCenterService;
use core_kernel_classes_Resource;
use oat\taoSync\model\SyncService;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\oatbox\user\User;
use oat\taoTestCenter\model\exception\TestCenterException;

/**
 * Class TestCenterService
 * @package oat\taoSync\model\testCenter
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class TestCenterService extends BaseTestCenterService
{
    /**
     * Assign user to the test center
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param User $user
     * @param core_kernel_classes_Resource $role
     * @return bool
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     * @throws \oat\taoTestCenter\model\exception\TestCenterException
     */
    public function assignUser(\core_kernel_classes_Resource $testCenter, User $user, \core_kernel_classes_Resource $role)
    {
        $userResource = $this->getResource($user->getIdentifier());

        if ($role->getUri() === SyncService::TAO_SYNC_ROLE) {
            $organisationIdProperty = $this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);
            $id = $this->getTestCenterOrganisationId($testCenter);
            $userResource->removePropertyValue($organisationIdProperty, $id);
            return $userResource->setPropertyValue($organisationIdProperty, $id) &&
                parent::assignUser($testCenter, $user, $role);
        }

        return parent::assignUser($testCenter, $user, $role);
    }

    /**
     * Unassign user from the test center
     *
     * @param core_kernel_classes_Resource $testCenter
     * @param User $user
     * @param core_kernel_classes_Resource $role
     * @return bool
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     * @throws \oat\taoTestCenter\model\exception\TestCenterException
     */
    public function unassignUser(\core_kernel_classes_Resource $testCenter, User $user, \core_kernel_classes_Resource $role)
    {
        $userResource = $this->getResource($user->getIdentifier());

        if ($role->getUri() === SyncService::TAO_SYNC_ROLE) {
            $id = $this->getTestCenterOrganisationId($testCenter);
            $organisationIdProperty = $this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);
            $userResource->removePropertyValue($organisationIdProperty, $id);
        }

        return parent::unassignUser($testCenter, $user, $role);
    }

    /**
     * Get the organisation id property of a test center
     *
     * @param \core_kernel_classes_Resource $testCenter
     * @return string
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     */
    public function getTestCenterOrganisationId(\core_kernel_classes_Resource $testCenter)
    {
        $property = $testCenter->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));
        if (is_null($property)) {
            throw new TestCenterException(__('TestCenter must have an organisation id property to associate it to sync manager(s).'));
        }
        return $property->literal;
    }

    /**
     * @param core_kernel_classes_Resource $role
     * @return \core_kernel_classes_Property
     * @throws TestCenterException
     */
    protected function getAssigneeProperty(\core_kernel_classes_Resource $role)
    {
        if ($role->getUri() === SyncService::TAO_SYNC_ROLE) {
            $prop = $this->getProperty(SyncService::PROPERTY_ASSIGNED_SYNC_USER);
        } else {
            $prop = parent::getAssigneeProperty($role);
        }
        return $prop;
    }
}
