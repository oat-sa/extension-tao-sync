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

namespace oat\taoSync\model\TestSession;

use oat\taoDelivery\model\execution\DeliveryExecution;

interface SyncTestSessionServiceInterface
{
    const SERVICE_ID = 'taoSync/SyncTestSessionService';

    const SYNC_ENTITY = 'sync session';

    /**
     * @param array $params
     */
    public function synchronizeTestSession(array $params = []);

    /**
     * @param array $session
     * @param array $params Synchronization params
     * @return
     */
    public function sendTestSessions(array $session, array $params = []);

    /**
     * @param array $session
     * @param array $params Synchronization params
     * @return array
     */
    public function importTestSessions(array $session, array $params = []);

    /**
     * @param DeliveryExecution $deliveryExecution
     * @return mixed
     */
    public function touchTestSession(DeliveryExecution $deliveryExecution);
}
