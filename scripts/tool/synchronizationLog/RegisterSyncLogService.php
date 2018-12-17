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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\scripts\tool\synchronizationLog;


use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\storage\RdsSyncLogStorage;
use oat\taoSync\model\SyncLog\SyncLogService;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

/**
 * Class SetupSyncLogService
 * @package oat\taoSync\scripts\tool\synchronizationLog
 */
class RegisterSyncLogService extends InstallAction
{
    /**
     * @inheritdoc
     */
    public function __invoke($params)
    {
        $syncLogService = new SyncLogService([
            SyncLogService::OPTION_STORAGE => RdsSyncLogStorage::SERVICE_ID
        ]);
        $this->registerService(SyncLogServiceInterface::SERVICE_ID, $syncLogService);

        return \common_report_Report::createSuccess('SyncLogService successfully registered.');
    }
}
