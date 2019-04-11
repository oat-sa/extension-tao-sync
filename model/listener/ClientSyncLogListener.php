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
use oat\taoSync\model\event\RequestStatsEvent;
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\event\SyncStartedEvent;
use oat\taoSync\model\SyncLog\SyncLogDataHelper;
use oat\taoSync\model\SyncLog\SyncLogDataParser;
use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

/**
 * Class ClientSyncLogListener
 * @package oat\taoSync\model\listener
 */
class ClientSyncLogListener extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/ClientSyncLogListener';

    /**
     * Create log record about started synchronization.
     *
     * @param SyncStartedEvent $event
     */
    public function logSyncStarted(SyncStartedEvent $event)
    {
        try {
            $syncLogService = $this->getSyncLogService();
            $params = $event->getSyncParameters();
            $syncLogEntity = new SyncLogEntity(
                $params[SyncLogServiceInterface::PARAM_SYNC_ID],
                $params[SyncLogServiceInterface::PARAM_BOX_ID],
                $params[SyncLogServiceInterface::PARAM_ORGANIZATION_ID],
                $this->parseSyncData($event->getReport()),
                SyncLogEntity::STATUS_IN_PROGRESS,
                $event->getReport(),
                new DateTime()
            );

            $syncLogService->create($syncLogEntity);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Update log record for finished synchronization.
     *
     * @param SyncFinishedEvent $event
     */
    public function logSyncFinished(SyncFinishedEvent $event)
    {
        try {
            $parameters = $event->getSyncParameters();
            $syncLogEntity = $this->getSyncLogService()->getBySyncIdAndBoxId($parameters['sync_id'], $parameters['box_id']);

            $report = $syncLogEntity->getReport();
            $report->add($event->getReport());
            $report->add($this->getConnectionStatsReport());

            if ($report->containsError()) {
                $syncLogEntity->setFailed();
            } else {
                $syncLogEntity->setCompleted();
            }
            $eventData = $this->parseSyncData($event->getReport());
            $newSyncData = SyncLogDataHelper::mergeSyncData($syncLogEntity->getData(), $eventData);

            $syncLogEntity->setData($newSyncData);
            $syncLogEntity->setFinishTime(new DateTime());
            $syncLogEntity->setClientState($this->parseClientState($syncLogEntity->getClientState(), $parameters));

            $this->getSyncLogService()->update($syncLogEntity);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Update log record for failed synchronization.
     *
     * @param SyncFailedEvent $event
     */
    public function logSyncFailed(SyncFailedEvent $event)
    {
        try {
            $parameters = $event->getSyncParameters();
            $syncId = $parameters[SyncLogServiceInterface::PARAM_SYNC_ID];
            $boxId = $parameters[SyncLogServiceInterface::PARAM_BOX_ID];
            $syncLogEntity = $this->getSyncLogService()->getBySyncIdAndBoxId($syncId, $boxId);

            $report = $syncLogEntity->getReport();
            $report->add($event->getReport());
            $report->add($this->getConnectionStatsReport());

            $eventData = $this->parseSyncData($event->getReport());
            $newSyncData = SyncLogDataHelper::mergeSyncData($syncLogEntity->getData(), $eventData);

            $syncLogEntity->setData($newSyncData);
            $syncLogEntity->setFailed();
            $syncLogEntity->setFinishTime(new DateTime());

            $this->getSyncLogService()->update($syncLogEntity);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    private $downloadSpeed = [];
    private $uploadSpeed = [];

    public function syncRequestStats(RequestStatsEvent $event)
    {
        $handlerStats = $event->getTransferStats()->getHandlerStats();

        if (!empty($handlerStats['speed_download'])) {
            $this->downloadSpeed[] = $handlerStats['speed_download'];
        }
        if (!empty($handlerStats['speed_upload'])) {
            $this->uploadSpeed[] = $handlerStats['speed_upload'];
        }
    }

    /**
     * @return \common_report_Report
     * @throws \common_exception_Error
     */
    public function getConnectionStatsReport()
    {
        $report = \common_report_Report::createInfo(__('Connection statistics'));
        $report->add(\common_report_Report::createInfo(__('Download speed: %s', $this->getDownloadSpeed())));
        $report->add(\common_report_Report::createInfo(__('Upload speed: %s', $this->getUploadSpeed())));

        return $report;
    }

    private function getDownloadSpeed()
    {
        return count($this->downloadSpeed) ? array_sum($this->downloadSpeed) / count($this->downloadSpeed) : 0;
    }

    private function getUploadSpeed()
    {
        return count($this->uploadSpeed) ? array_sum($this->uploadSpeed) / count($this->uploadSpeed) : 0;
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

    private function parseClientState(array $clientState, $params)
    {
        if (isset($params['tao_version'])) {
            $clientState['tao_version'] = $params['tao_version'];
        }

        return $clientState;
    }
}
