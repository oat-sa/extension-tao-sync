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
use oat\taoSync\model\event\SynchronizationFailed;
use oat\taoSync\model\event\SynchronizationFinished;
use oat\taoSync\model\event\SynchronizationStarted;
use oat\taoSync\model\event\SynchronizationUpdated;
use oat\taoSync\model\exception\SyncLogEntityNotFound;
use oat\taoSync\model\SyncLog\SyncLogDataHelper;
use oat\taoSync\model\SyncLog\SyncLogDataParser;
use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

/**
 * Class CentralSyncLogListener
 * @package oat\taoSync\model\listener
 */
class CentralSyncLogListener implements SyncLogListenerInterface
{
    /**
     * @param SynchronizationStarted $event
     * @return mixed|void
     * @throws \common_exception_Error
     */
    public function logSyncStarted(SynchronizationStarted $event)
    {
        try {
            $this->logSyncUpdated($event);
        } catch (SyncLogEntityNotFound $e) {
            $params = $event->getSyncParameters();
            $this->validateParameters($params);
            $syncLogService = $this->getSyncLogService();

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
            return;
        }
    }

    /**
     * @param SynchronizationUpdated $event
     * @return mixed|void
     */
    public function logSyncUpdated(SynchronizationUpdated $event)
    {
        try {
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
        } catch (SyncLogEntityNotFound $e) {
            throw $e;
        } catch (\Exception $e) {
            return;
        }
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
