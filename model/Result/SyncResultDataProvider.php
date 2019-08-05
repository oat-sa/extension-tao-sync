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

namespace oat\taoSync\model\Result;

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\Monitoring;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoResultServer\models\classes\ResultManagement;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoSync\model\history\ResultSyncHistoryService;

class SyncResultDataProvider extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncResultDataProvider';

    const OPTION_STATUS_EXECUTIONS_TO_SYNC = 'statusExecutionsToSync';

    /**
     * @param $chunkSize
     * @return \Generator
     * @throws \common_exception_Error
     * @throws \common_exception_NoImplementation
     * @throws \common_exception_NotFound
     */
    public function getDeliveryExecutions($chunkSize)
    {
        $deliveryExecutions = [];
        $counter = 0;

        /** @var \core_kernel_classes_Resource $delivery */
        foreach ($this->getDeliveryAssemblyService()->getAllAssemblies() as $delivery) {
            /** @var DeliveryExecution $deliveryExecution */
            foreach ($this->getDeliveryExecutionByDelivery($delivery) as $deliveryExecution) {
                $deliveryExecutionId = $deliveryExecution->getIdentifier();
                $statesToSync = $this->getExecutionsStatesAvailableForSync();
                $currentState = $deliveryExecution->getState()->getUri();

                // Skip non white listed states of delivery executions.
                if (!in_array($currentState, $statesToSync)){
                    continue;
                }

                // Do not resend delivery execution already exported
                if ($this->getResultSyncHistory()->isAlreadyExported($deliveryExecutionId)) {
                    continue;
                }

                $deliveryExecutions[] = $deliveryExecution;

                $counter++;

                if ($counter % $chunkSize === 0) {
                    yield $deliveryExecutions;
                    $deliveryExecutions = [];
                }
            }
        }

        yield $deliveryExecutions;
    }

    /**
     * @return DeliveryAssemblyService
     */
    protected function getDeliveryAssemblyService()
    {
        return DeliveryAssemblyService::singleton();
    }

    /**
     * @return ServiceProxy
     */
    protected function getDeliveryExecutionService()
    {
        return $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
    }

    /**
     * Fetch the delivery result server from delivery
     *
     * @param $deliveryId
     * @return ResultManagement | \taoResultServer_models_classes_WritableResultStorage
     */
    protected function getResultStorage($deliveryId)
    {
        return $this->getServiceLocator()->get(ResultServerService::SERVICE_ID)->getResultStorage($deliveryId);
    }

    /**
     * @return ResultSyncHistoryService
     */
    protected function getResultSyncHistory()
    {
        return $this->getServiceLocator()->get(ResultSyncHistoryService::SERVICE_ID);
    }

    /**
     * Get delivery executions by delivery
     *
     * @param \core_kernel_classes_Resource $delivery
     * @return array|DeliveryExecution[]
     * @throws \common_exception_Error
     * @throws \common_exception_NoImplementation
     */
    protected function getDeliveryExecutionByDelivery(\core_kernel_classes_Resource $delivery)
    {
        $serviceProxy = $this->getDeliveryExecutionService();
        if (!$serviceProxy instanceof Monitoring) {
            $resultStorage = $this->getResultStorage($delivery->getUri());
            $results = $resultStorage->getResultByDelivery([$delivery->getUri()]);
            $executions = [];
            foreach ($results as $result) {
                $executions[] = $serviceProxy->getDeliveryExecution($result['deliveryResultIdentifier']);
            }
        } else{
            $executions = $serviceProxy->getExecutionsByDelivery($delivery);
        }
        return $executions;
    }

    /**
     * @return array
     */
    protected function getExecutionsStatesAvailableForSync()
    {
        $statuses = $this->getOption(static::OPTION_STATUS_EXECUTIONS_TO_SYNC);
        if ($statuses == null){
            return [DeliveryExecution::STATE_FINISHED];
        }

        return $statuses;
    }
}