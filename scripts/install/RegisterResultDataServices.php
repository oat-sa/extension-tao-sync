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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoSync\model\Result\SyncResultDataFormatter;
use oat\taoSync\model\Result\SyncResultDataProvider;


/**
 * Class RegisterResultDataServices
 *
 * Register the result data services
 *
 * @package oat\taoSync\scripts\install
 */
class RegisterResultDataServices extends InstallAction
{
    public function __invoke($params)
    {
        $syncResultDataProvider = new SyncResultDataProvider(
            [SyncResultDataProvider::OPTION_STATUS_EXECUTIONS_TO_SYNC => [DeliveryExecution::STATE_FINISHIED]]
        );
        $this->registerService(SyncResultDataProvider::SERVICE_ID, $syncResultDataProvider);
        $syncResultsDataFormatter = new SyncResultDataFormatter([]);
        $this->registerService(SyncResultDataFormatter::SERVICE_ID, $syncResultsDataFormatter);
        return \common_report_Report::createSuccess('ResultData services successfully registered.');
    }

}