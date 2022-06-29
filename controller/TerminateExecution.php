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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\controller;

use oat\generis\model\OntologyAwareTrait;
use oat\taoSync\model\Execution\DeliveryExecutionStatusManager;
use tao_actions_CommonModule;
use common_http_Request as Request;

class TerminateExecution extends tao_actions_CommonModule
{
    use OntologyAwareTrait;

    public function terminateExecutions()
    {
        try {
            $request = Request::currentRequest();
            $this->validateRequest($request);

            $executionIds = $this->getExecutionsFromRequest($request);
            $this->getServiceLocator()->get(DeliveryExecutionStatusManager::SERVICE_ID)
                ->terminateDeliveryExecutions($executionIds);

            $this->returnJson([
                'success' => true,
                'data' => [
                    'message' => __('Executions were successfully terminated.'),
                ]
            ]);
        } catch (\common_exception_ClientException $e) {
            $this->returnJson([
                'success' => false,
                'errorMsg' => $e->getUserMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->returnJson([
                'success' => false,
                'errorMsg' => 'Unexpected error.'
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getExecutionsFromRequest(Request $request)
    {
        $requestParameters = $request->getParams();

        if (!isset($requestParameters['executionsId']) || !is_array($requestParameters['executionsId'])) {
            throw new \common_exception_ValidationFailed('executionsId', 'Executions IDs parameter must be an array.');
        }

        return $requestParameters['executionsId'];
    }

    /**
     * @param Request $request
     *
     * @throws \common_exception_MethodNotAllowed
     * @throws \common_exception_Unauthorized
     */
    private function validateRequest(Request $request)
    {
        if ($request->getMethod() !== 'POST') {
            throw new \common_exception_MethodNotAllowed('Only POST requests are allowed.');
        }

        $this->validateCsrf();
    }
}
