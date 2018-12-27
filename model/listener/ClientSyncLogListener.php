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

use DateTime;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\event\SynchronizationFailed;
use oat\taoSync\model\event\SynchronizationFinished;
use oat\taoSync\model\event\SynchronizationStarted;
use oat\taoSync\model\event\SynchronizationUpdated;
use oat\taoSync\model\SyncLog\SyncLogDataHelper;
use oat\taoSync\model\SyncLog\SyncLogDataParser;
use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

/**
 * Class ClientSyncLogListener
 * @package oat\taoSync\model\listener
 */
class ClientSyncLogListener extends ConfigurableService implements SyncLogListenerInterface
{
    /**
     * Create log record about started synchronization.
     *
     * @param SynchronizationStarted $event
     */
    public function logSyncStarted(SynchronizationStarted $event)
    {
        $syncLogService = $this->getSyncLogService();
        $params = $event->getSyncParameters();
        $syncLogEntity = new SyncLogEntity(
            $params['sync_id'],
            $params['box_id'],
            $params['organisation_id'],
            $this->parseSyncData($event->getReport()),
            SyncLogEntity::STATUS_IN_PROGRESS,
            $event->getReport(),
            new DateTime()
        );

        $syncLogService->create($syncLogEntity);
    }

    /**
     * @param SynchronizationUpdated $event
     * @return mixed|void
     */
    public function logSyncUpdated(SynchronizationUpdated $event)
    {
        // TODO: Implement logSyncUpdated() method.
    }

    /**
     * Update log record for finished synchronization.
     *
     * @param SynchronizationFinished $event
     */
    public function logSyncFinished(SynchronizationFinished $event)
    {
        $parameters = $event->getSyncParameters();
        $syncLogEntity = $this->getSyncLogService()->getBySyncIdAndBoxId($parameters['sync_id'], $parameters['box_id']);

        $eventReport = $event->getReport();
        if ($eventReport->containsError()) {
            $syncLogEntity->setFailed();
        } else {
            $syncLogEntity->setCompleted();
        }

        $eventData = $this->parseSyncData($event->getReport());
        $newSyncData = SyncLogDataHelper::mergeSyncData($syncLogEntity->getData(), $eventData);
        $syncLogEntity->setData($newSyncData);

        $syncLogEntity->setReport($eventReport);
        $syncLogEntity->setFinishTime(new DateTime());

        $this->getSyncLogService()->update($syncLogEntity);
    }

    /**
     * Update log record for failed synchronization.
     *
     * @param SynchronizationFailed $event
     */
    public function logSyncFailed(SynchronizationFailed $event)
    {
        $parameters = $event->getSyncParameters();
        $syncLogEntity = $this->getSyncLogService()->getBySyncIdAndBoxId($parameters['sync_id'], $parameters['box_id']);

        $eventData = $this->parseSyncData($event->getReport());
        $newSyncData = SyncLogDataHelper::mergeSyncData($syncLogEntity->getData(), $eventData);
        $syncLogEntity->setData($newSyncData);

        $syncLogEntity->setFailed();
        $syncLogEntity->setReport($event->getReport());
        $syncLogEntity->setFinishTime(new DateTime());

        $this->getSyncLogService()->update($syncLogEntity);
    }


    /**
     * @param \common_report_Report $report
     * @return array
     */
    private function parseSyncData(\common_report_Report $report)
    {
        /** @var SyncLogDataParser $formatter */
        $formatter = $this->getServiceLocator()->get(SyncLogDataParser::SERVICE_ID);

        return $formatter->parseSyncData($report);
    }

    /**
     * @return SyncLogServiceInterface
     */
    private function getSyncLogService()
    {
        return $this->getServiceLocator()->get(SyncLogServiceInterface::SERVICE_ID);
    }
}
