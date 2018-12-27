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

use oat\taoSync\model\event\SynchronizationFailed;
use oat\taoSync\model\event\SynchronizationFinished;
use oat\taoSync\model\event\SynchronizationStarted;
use oat\taoSync\model\event\SynchronizationUpdated;

interface SyncLogListenerInterface
{
    const SERVICE_ID = 'taoSync/SynchronizationLogListener';

    /**
     * Create log record about started synchronization.
     *
     * @param SynchronizationStarted $event
     * @return mixed
     */
    public function logSyncStarted(SynchronizationStarted $event);

    /**
     * Update synchronization log record with ned details.
     *
     * @param SynchronizationUpdated $event
     * @return mixed
     */
    public function logSyncUpdated(SynchronizationUpdated $event);

    /**
     * Update log record for finished synchronization.
     *
     * @param SynchronizationFinished $event
     */
    public function logSyncFinished(SynchronizationFinished $event);

    /**
     * Update log record for failed synchronization.
     *
     * @param SynchronizationFailed $event
     */
    public function logSyncFailed(SynchronizationFailed $event);
}
