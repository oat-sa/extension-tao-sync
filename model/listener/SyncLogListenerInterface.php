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

namespace oat\taoSync\model\listener;

use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\event\SyncStartedEvent;
use oat\taoSync\model\event\SyncResponseEvent;

interface SyncLogListenerInterface
{
    const SERVICE_ID = 'taoSync/SynchronizationLogListener';

    /**
     * Create log record about started synchronization.
     *
     * @param SyncStartedEvent $event
     * @return mixed
     */
    public function logSyncStarted(SyncStartedEvent $event);

    /**
     * Update synchronization log record with ned details.
     *
     * @param SyncResponseEvent $event
     * @return mixed
     */
    public function logSyncUpdated(SyncResponseEvent $event);

    /**
     * Update log record for finished synchronization.
     *
     * @param SyncFinishedEvent $event
     */
    public function logSyncFinished(SyncFinishedEvent $event);

    /**
     * Update log record for failed synchronization.
     *
     * @param SyncFailedEvent $event
     */
    public function logSyncFailed(SyncFailedEvent $event);
}
