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

namespace oat\taoSync\model\Execution;

use common_exception_NotFound;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoItems\model\preview\OntologyItemNotFoundException;
use oat\taoProctoring\model\DeliveryExecutionStateService;
use oat\taoProctoring\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;

class DeliveryExecutionStatusManager extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/DeliveryExecutionStatusManager';

    /**
     * @return array
     */
    protected function getNotFinalStatuses()
    {
        return [
            DeliveryExecutionInterface::STATE_ACTIVE,
            DeliveryExecutionInterface::STATE_PAUSED,
            DeliveryExecution::STATE_AWAITING,
            DeliveryExecution::STATE_AUTHORIZED,
        ];
    }

    /**
     * @return DeliveryMonitoringData[]
     */
    public function getExecutionsInProgress()
    {
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        return $deliveryMonitoringService->find([
            DeliveryMonitoringService::STATUS => $this->getNotFinalStatuses()
        ], []);
    }


    /**
     * @param array $executionIds
     * @throws OntologyItemNotFoundException
     */
    public function terminateDeliveryExecutions(array $executionIds)
    {
        try {
            $executions = $this->getDeliveryExecutions($executionIds);

            /** @var DeliveryExecutionStateService $executionStateService */
            $executionStateService = $this->getServiceLocator()->get(DeliveryExecutionStateService::SERVICE_ID);

            foreach ($executions as $execution) {
                $executionStateService->terminateExecution($execution, [
                    'reasons' => [
                        'category' => 'Technical'
                    ],
                    'comment' => 'Terminated to proceed with synchronization.'
                ]);
            }
        } catch (common_exception_NotFound $e) {
            throw new OntologyItemNotFoundException('Delivery execution not found');
        }

        return true;
    }

    /**
     * @param array $executionIds
     * @return array
     *
     * @throws common_exception_NotFound
     */
    private function getDeliveryExecutions(array $executionIds)
    {
        $executions = [];
        /** @var ServiceProxy $serviceProxy */
        $serviceProxy = $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);

        foreach ($executionIds as $id) {
            $executions[] = $serviceProxy->getDeliveryExecution($id);
        }

        return $executions;
    }
}
