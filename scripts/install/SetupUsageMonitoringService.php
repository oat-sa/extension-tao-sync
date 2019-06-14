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
namespace oat\taoSync\scripts\install;

use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\monitoring\database\Mysql;
use oat\taoSync\model\monitoring\database\Postgresql;
use oat\taoSync\model\monitoring\DataSpaceUsageService;
use oat\taoSync\model\OfflineMachineChecksService;

class SetupUsageMonitoringService extends InstallAction
{
    public function __invoke($params)
    {
        $service = new OfflineMachineChecksService([
            OfflineMachineChecksService::OPTION_CHECKS => [
                new DataSpaceUsageService(),
                $this->getServiceManager()->get(\common_persistence_Manager::SERVICE_ID)
                    ->getPersistenceById('default')
                    ->getPlatform()->getName() === 'postgresql'
                    ? new Postgresql()
                    : new Mysql()
            ]
        ]);
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
        $this->getServiceManager()->register(OfflineMachineChecksService::SERVICE_ID, $service);
        $eventManager->attach(SyncFinishedEvent::class, [OfflineMachineChecksService::SERVICE_ID, 'listen']);
        $eventManager->attach(SyncFailedEvent::class, [OfflineMachineChecksService::SERVICE_ID, 'listen']);
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
        return \common_report_Report::createInfo('OfflineStats Services Setup');
    }
}
