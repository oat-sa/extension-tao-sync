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
 * Copyright (c) 2019 Open Assessment Technologies SA
 */

namespace oat\taoSync\controller;

use oat\generis\model\OntologyAwareTrait;
use oat\taoSync\model\testCenter\SyncManagerTreeService;

class SyncManagerTree extends \tao_actions_CommonModule
{
    use OntologyAwareTrait;

    const RESOURCE_URI_REQUEST_PARAM_KEY = 'resourceUri';

    const IS_FORCED_REQUEST_PARAM_KEY = 'isForced';

    /**
     * Entry point to save the value sent form the form
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

        /** @var SyncManagerTreeService  $service */
        $service = $this->getServiceLocator()->get(SyncManagerTreeService::SERVICE_ID);
        $testCenterDomain = $service->createTestCenterDomain(
            $this->getPostParameter(self::RESOURCE_URI_REQUEST_PARAM_KEY)
        );
        $userUri = $this->getUserUriFromRequest();

        if (!$userUri) {
            return $this->returnJson(['saved' => $service->unassignAllUsersFromTestCenter($testCenterDomain)]);
        }
        $user = $this->getResource($userUri);
        $existTestCenters = $service->getUserTestCenters($user);

        if (
            count($existTestCenters) === 1
            && current($existTestCenters) === $testCenterDomain->getTestCenter()->getUri()
        ) {
            return $this->returnJson(
                ['saved' => $service->unassignOthersUsersFromTestCenter($testCenterDomain, $user)]
            );
        }

        if (count($existTestCenters) && !$this->getPostParameter(self::IS_FORCED_REQUEST_PARAM_KEY)) {
            return $this->returnJson(
                [
                    'saved' => false,
                    'needApprove' => true,
                    'testCenters' => implode(
                        ', ',
                        array_map(
                            function ($testCenterUri) {
                                return $this->getResource($testCenterUri)->getLabel();
                            },
                            array_diff($existTestCenters, [$testCenterDomain->getTestCenter()->getUri()])
                        )
                    )
                ]
            );
        }
        return $this->returnJson(['saved' => $service->saveReversedValues($testCenterDomain, $user)]);
    }

    /**
     * @return string|bool
     * @throws \common_Exception
     */
    private function getUserUriFromRequest()
    {
        $values = \tao_helpers_form_GenerisTreeForm::getSelectedInstancesFromPost();

        if (count($values) > 1) {
            throw new \common_Exception(__('TestCenter must have only ony Sync Manager'));
        }
        return current($values);
    }
}
