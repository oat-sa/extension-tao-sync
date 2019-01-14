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
namespace oat\taoSync\model\listener;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\event\SyncFinishedEvent;
/**
 * Class SyncStatusListener
 * @package oat\taoSync\model\listener
 */
class SyncStatusListener extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncStatusListener';
    /**
     * @param SyncFinishedEvent $event
     */
    public function sendSyncFinishedConfirmation(SyncFinishedEvent $event)
    {
        try {
            /** @var SynchronisationClient $syncClient */
            $syncClient = $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
            $response = $syncClient->sendSyncFinishedConfirmation($event->getSyncParameters());
            $this->logInfo(json_encode($response));
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }
}
