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

namespace oat\taoSync\scripts\tool\SyncLog;

use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\listener\SyncStatusListener;

/**
 * Class RegisterSyncStatusListener
 * @package oat\taoSync\scripts\tool\synchronizationLog
 */
class RegisterSyncStatusListener extends InstallAction
{
    /**
     * @inheritdoc
     */
    public function __invoke($params)
    {
        $this->registerEvent(SyncFinishedEvent::class, [SyncStatusListener::SERVICE_ID, 'sendSyncFinishedConfirmation']);
        $this->registerEvent(SyncFailedEvent::class, [SyncStatusListener::SERVICE_ID, 'sendSyncFailedConfirmation']);

        $syncStatusListener = new SyncStatusListener([]);
        $this->getServiceManager()->register(SyncStatusListener::SERVICE_ID, $syncStatusListener);

        return \common_report_Report::createSuccess('SyncStatusListener successfully registered.');
    }
}
