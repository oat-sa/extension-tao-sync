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
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliveryRdf\helper\DetectTestAndItemIdentifiersHelper;
use oat\taoResultServer\models\classes\ResultManagement;
use oat\taoResultServer\models\classes\ResultServerService;

class SyncResultDataFormatter extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncResultsDataFormatter';

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return array
     * @throws \common_exception_NotFound
     * @throws \core_kernel_persistence_Exception
     */
    public function format($deliveryExecution)
    {
        $deliveryExecutionId = $deliveryExecution->getIdentifier();
        $deliveryId = $deliveryExecution->getDelivery()->getUri();

        // Do no send delivery execution with no variables (deleted)
        $variables = $this->getDeliveryExecutionVariables($deliveryId, $deliveryExecutionId);
        if (empty($variables)) {
            null;
        }

        return [
            'deliveryId' => $deliveryId,
            'deliveryExecutionId' => $deliveryExecutionId,
            'details' => $this->getDeliveryExecutionDetails($deliveryExecutionId),
            'variables' => $variables,
        ];
    }

    /**
     * Get variables of a delivery execution
     *
     * @param $deliveryId
     * @param $deliveryExecutionId
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    protected function getDeliveryExecutionVariables($deliveryId, $deliveryExecutionId)
    {
        $variables = $this->getResultStorage($deliveryId)->getDeliveryVariables($deliveryExecutionId);
        $deliveryExecutionVariables = [];
        foreach ($variables as $variable) {
            $variable = (array) $variable[0];
            list($testIdentifier,$itemIdentifier) = $this->detectTestAndItemIdentifiers($deliveryId, $variable);
            $deliveryExecutionVariables[] = [
                'type' => $variable['class'],
                'callIdTest' => isset($variable['callIdTest'])? $variable['callIdTest'] : null,
                'callIdItem' => isset($variable['callIdItem']) ? $variable['callIdItem'] : null,
                'test' => $testIdentifier,
                'item' => $itemIdentifier,
                'data' => $variable['variable'],
            ];
        }

        return $deliveryExecutionVariables;
    }

    /**
     * @param $deliveryId
     * @param $variable
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    private function detectTestAndItemIdentifiers($deliveryId, $variable)
    {
        $test = isset($variable['test']) ? $variable['test'] : null;
        $item = isset($variable['item']) ? $variable['item'] : null;
        return (new DetectTestAndItemIdentifiersHelper())->detect($deliveryId, $test, $item);
    }

    /**
     * Get details of a delivery execution
     *
     * @param $deliveryExecutionId
     * @return array
     */
    private function getDeliveryExecutionDetails($deliveryExecutionId)
    {
        /** @var DeliveryExecution $deliveryExecution */
        $deliveryExecution = $this->getDeliveryExecutionService()->getDeliveryExecution($deliveryExecutionId);
        try {
            return [
                'identifier' => $deliveryExecution->getIdentifier(),
                'label' => $deliveryExecution->getLabel(),
                'test-taker' => $deliveryExecution->getUserIdentifier(),
                'starttime' => $deliveryExecution->getStartTime(),
                'finishtime' => $deliveryExecution->getFinishTime(),
                'state' => $deliveryExecution->getState()->getUri(),
            ];
        } catch (\common_exception_NotFound $e) {
            return [];
        }
    }

    /**
     * @return ServiceProxy
     */
    private function getDeliveryExecutionService()
    {
        return $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
    }

    /**
     * Fetch the delivery result server from delivery
     *
     * @param $deliveryId
     * @return ResultManagement | \taoResultServer_models_classes_WritableResultStorage
     */
    private function getResultStorage($deliveryId)
    {
        return $this->getServiceLocator()->get(ResultServerService::SERVICE_ID)->getResultStorage($deliveryId);
    }
}