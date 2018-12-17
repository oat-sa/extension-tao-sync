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
use oat\taoSync\model\event\SynchronizationFinished;
use oat\taoSync\model\event\SynchronizationStarted;
use oat\taoSync\model\SyncLog\SyncLogDataParser;
use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

/**
 * Class SynchronizationLogListener
 * @package oat\taoSync\model\listener
 */
class SyncLogListener extends ConfigurableService implements SyncLogListenerInterface
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
            $params['tao_box_id'],
            $params['organisation_id'],
            $this->parseSyncData($event->getReport()),
            SyncLogEntity::STATUS_IN_PROGRESS,
            $event->getReport(),
            new DateTime()
        );

        $syncLogService->create($syncLogEntity);
    }

    /**
     * Update log record for finished synchronization.
     *
     * @param SynchronizationFinished $event
     */
    public function logSyncFinished(SynchronizationFinished $event)
    {
        $parameters = $event->getSyncParameters();
        $syncLogEntity = $this->getSyncLogService()->getBySyncIdAndClientId($parameters['sync_id'], $parameters['client_id']);

        $eventReport = $event->getReport();
        if (count($eventReport->getErrors())) {
            $syncLogEntity->setFailed();
        } else {
            $syncLogEntity->setCompleted();
        }
        $syncLogEntity->setData($this->parseSyncData($eventReport));
        $syncLogEntity->setReport($eventReport);
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
