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
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\OfflineMachineChecksService;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

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
            $syncParams = $event->getSyncParameters();
            $syncParams[SyncLogServiceInterface::PARAM_CLIENT_STATE] = $this->getOfflineMachineChecksService()->getReport()->toArray();
            $response = $syncClient->sendSyncFinishedConfirmation($syncParams);
            $this->logInfo(json_encode($response));
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * @param SyncFailedEvent $event
     */
    public function sendSyncFailedConfirmation(SyncFailedEvent $event)
    {
        try {
            /** @var SynchronisationClient $syncClient */
            $syncClient = $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
            $syncParams = $event->getSyncParameters();
            $response = $syncClient->sendSyncFailedConfirmation($syncParams, $this->getFailingReason($event));
            $this->logInfo(json_encode($response));
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * @return OfflineMachineChecksService
     */
    protected function getOfflineMachineChecksService()
    {
        return $this->getServiceLocator()->get(OfflineMachineChecksService::SERVICE_ID);
    }

    /**
     * @param SyncFailedEvent $event
     * @return string|array
     */
    private function getFailingReason(SyncFailedEvent $event)
    {
        if ($event->getReason()) {
            return $event->getReason();
        }

        return $event->getReport()->getErrors(true);
    }
}
