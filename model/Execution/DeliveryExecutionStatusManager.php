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

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
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
        ], [], true);
    }
}
