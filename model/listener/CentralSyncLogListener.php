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

/**
 * Class CentralSyncLogListener
 * @package oat\taoSync\model\listener
 */
class CentralSyncLogListener implements SyncLogListenerInterface
{
    /**
     * @param SynchronizationStarted $event
     * @return mixed|void
     */
    public function logSyncStarted(SynchronizationStarted $event)
    {
        // TODO: Implement logSyncStarted() method.
    }

    /**
     * @param SynchronizationFinished $event
     */
    public function logSyncFinished(SynchronizationFinished $event)
    {
        // TODO: Implement logSyncFinished() method.
    }

    /**
     * @param SynchronizationFailed $event
     */
    public function logSyncFailed(SynchronizationFailed $event)
    {
        // TODO: Implement logSyncFailed() method.
    }
}
