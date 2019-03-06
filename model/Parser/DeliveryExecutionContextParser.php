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

namespace oat\taoSync\model\Parser;

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecutionContextInterface;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;

class DeliveryExecutionContextParser extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/DeliveryExecutionContextParser';

    /**
     * @param array $deliveryMonitoringData
     * @return array
     */
    public function parseExecutionContextDetails(array $deliveryMonitoringData)
    {
        $executionContextData = [];

        /** @var DeliveryMonitoringData $monitoringData */
        foreach ($deliveryMonitoringData as $monitoringData) {
            if (!$monitoringData instanceof DeliveryMonitoringData) {
                continue;
            }

            $deliveryExecutionContext = $monitoringData->getDeliveryExecutionContext();
            if ($deliveryExecutionContext instanceof DeliveryExecutionContextInterface) {
                $executionContextData[] = $deliveryExecutionContext;
            }
        }

        return $executionContextData;
    }
}
