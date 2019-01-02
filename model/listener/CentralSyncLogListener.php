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
use common_report_Report as Report;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\event\AbstractSyncEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\event\SyncRequestEvent;
use oat\taoSync\model\event\SyncResponseEvent;
use oat\taoSync\model\exception\SyncLogEntityNotFound;
use oat\taoSync\model\SyncLog\SyncLogDataHelper;
use oat\taoSync\model\SyncLog\SyncLogDataParser;
use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

/**
 * Class CentralSyncLogListener
 * @package oat\taoSync\model\listener
 */
class CentralSyncLogListener extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/CentralSyncLogListener';

    /**
     * Handler synchronization request event.
     *
     * @param SyncRequestEvent $event
     */
    public function logSyncRequest(SyncRequestEvent $event)
    {
        try {
            $this->updateSyncLogRecord($event);
        } catch (SyncLogEntityNotFound $e) {
            $params = $event->getSyncParameters();
            $this->validateParameters($params);
            $syncLogService = $this->getSyncLogService();

            $report = Report::createInfo('Synchronization started...');
            $report->add($event->getReport());
            $syncLogEntity = new SyncLogEntity(
                $params[SyncLogServiceInterface::PARAM_SYNC_ID],
                $params[SyncLogServiceInterface::PARAM_BOX_ID],
                $params[SyncLogServiceInterface::PARAM_ORGANIZATION_ID],
                $this->parseSyncData($report),
                SyncLogEntity::STATUS_IN_PROGRESS,
                $report,
                new DateTime()
            );

            $syncLogService->create($syncLogEntity);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Handle synchronization response event.
     *
     * @param SyncResponseEvent $event
     */
    public function logSyncResponse(SyncResponseEvent $event)
    {
        try {
            $this->updateSyncLogRecord($event);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Update synchronization response record.
     *
     * @param AbstractSyncEvent $event
     * @throws \common_exception_Error
     */
    private function updateSyncLogRecord(AbstractSyncEvent $event)
    {
        $params = $event->getSyncParameters();
        $this->validateParameters($params);
        $syncLogService = $this->getSyncLogService();

        /** @var SyncLogEntity $syncLogEntity */
        $syncLogEntity = $syncLogService->getBySyncIdAndBoxId($params[SyncLogServiceInterface::PARAM_SYNC_ID], $params[SyncLogServiceInterface::PARAM_BOX_ID]);

        $report = $syncLogEntity->getReport();
        $report->add($event->getReport());

        $eventData = $this->parseSyncData($event->getReport());
        $newSyncData = SyncLogDataHelper::mergeSyncData($syncLogEntity->getData(), $eventData);
        $syncLogEntity->setData($newSyncData);

        $syncLogService->update($syncLogEntity);
    }

    /**
     * @param SyncFinishedEvent $event
     */
    public function logSyncFinished(SyncFinishedEvent $event)
    {
        try {
            $params = $event->getSyncParameters();
            $this->validateParameters($params);
            $syncLogService = $this->getSyncLogService();

            /** @var SyncLogEntity $syncLogEntity */
            $syncLogEntity = $syncLogService->getBySyncIdAndBoxId($params[SyncLogServiceInterface::PARAM_SYNC_ID], $params[SyncLogServiceInterface::PARAM_BOX_ID]);

            $report = $syncLogEntity->getReport();
            $report->add($event->getReport());
            if ($report->containsError()) {
                $syncLogEntity->setFailed();
            } else {
                $syncLogEntity->setCompleted();
            }

            $syncLogService->update($syncLogEntity);
        } catch (\Exception $e) {
            return;
        }
    }

    private function validateParameters(array $params)
    {
        if (empty($params[SyncLogServiceInterface::PARAM_SYNC_ID])) {
            throw new \InvalidArgumentException('Required synchronization parameter is missing: ' . SyncLogServiceInterface::PARAM_SYNC_ID);
        }
        if (empty($params[SyncLogServiceInterface::PARAM_BOX_ID])) {
            throw new \InvalidArgumentException('Required synchronization parameter is missing: ' . SyncLogServiceInterface::PARAM_BOX_ID);
        }
        if (empty($params[SyncLogServiceInterface::PARAM_ORGANIZATION_ID])) {
            throw new \InvalidArgumentException('Required synchronization parameter is missing: ' . SyncLogServiceInterface::PARAM_ORGANIZATION_ID);
        }

        return true;
    }

    /**
     * @param Report $report
     * @return array
     */
    private function parseSyncData(Report $report)
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
