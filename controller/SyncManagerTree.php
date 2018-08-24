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
 * Copyright (c) 2018 Open Assessment Technologies SA
 */

namespace oat\taoSync\controller;


use oat\generis\model\OntologyAwareTrait;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\SyncService;

class SyncManagerTree extends \tao_actions_CommonModule
{
    use OntologyAwareTrait;

    /**
     * Entrypoint to save the value sent form the form
     *
     * @throws \common_Exception
     * @throws \common_exception_IsAjaxAction
     * @throws \core_kernel_persistence_Exception
     */
    public function setReverseValues()
    {
        if (!\tao_helpers_Request::isAjax()) {
            throw new \common_exception_IsAjaxAction(__FUNCTION__);
        }

        $values = \tao_helpers_form_GenerisTreeForm::getSelectedInstancesFromPost();
        $resource = $this->getResource($this->getRequestParameter('resourceUri'));

        $success = $this->saveReversedValues($resource, $values);

        echo json_encode(array('saved' => $success));
    }

    /**
     * Save the values sent from the SyncUser form
     *
     * @param \core_kernel_classes_Resource $testCenter
     * @param array $values
     * @return bool
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     */
    protected function saveReversedValues(\core_kernel_classes_Resource $testCenter, array $values)
    {
        $id = $this->getTestCenterOrganisationId($testCenter);
        $assignedSyncUserProperty = $this->getProperty(SyncService::PROPERTY_ASSIGNED_SYNC_USER);
        $organisationIdProperty = $this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);

        $success = true;
        foreach ($values as $uri) {
            $syncUser = $this->getResource($uri);
            $success = $success && $syncUser->editPropertyValues($assignedSyncUserProperty, $testCenter);
            $success = $success && $syncUser->editPropertyValues($organisationIdProperty, $id);
        }

        return $success;
    }

    /**
     * Get the organisation id property of a test center
     *
     * @param \core_kernel_classes_Resource $testCenter
     * @return string
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     */
    protected function getTestCenterOrganisationId(\core_kernel_classes_Resource $testCenter)
    {
        $property = $testCenter->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));
        if (is_null($property)) {
            throw new \common_Exception(__('TestCenter must have an organisation id property to associate it to sync manager(s).'));
        }
        return $property->literal;
    }
}