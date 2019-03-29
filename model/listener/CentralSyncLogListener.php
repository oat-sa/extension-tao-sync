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
use Exception;
use common_exception_Error;
use common_report_Report as Report;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\event\AbstractSyncEvent;
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\event\SyncRequestEvent;
use oat\taoSync\model\event\SyncResponseEvent;
use oat\taoSync\model\Exception\SyncLogEntityNotFound;
use oat\taoSync\model\SyncLog\SyncLogClientStateParser;
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
            $syncLogEntity = $this->getSyncLogEntityForEvent($event);
            $this->updateSyncLogRecord($syncLogEntity, $event);
        } catch (Exception $e) {
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
            $syncLogEntity = $this->getSyncLogEntityForEvent($event);
            $this->updateSyncLogRecord($syncLogEntity, $event);
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * Update synchronization response record.
     *
     * @param SyncLogEntity $syncLogEntity
     * @param AbstractSyncEvent $event
     * @throws common_exception_Error
     */
    private function updateSyncLogRecord(SyncLogEntity $syncLogEntity, AbstractSyncEvent $event)
    {
        $report = $syncLogEntity->getReport();
        $report->add($event->getReport());

        $eventData = $this->parseSyncData($event->getReport());
        $newSyncData = SyncLogDataHelper::mergeSyncData($syncLogEntity->getData(), $eventData);
        $syncLogEntity->setData($newSyncData);

        $syncLogService = $this->getSyncLogService();
        $syncLogService->update($syncLogEntity);
    }

    /**
     * Create synchronization log record.
     *
     * @param AbstractSyncEvent $event
     * @return SyncLogEntity
     *
     * @throws common_exception_Error
     */
    private function createSyncLogRecord(AbstractSyncEvent $event)
    {
        $params = $event->getSyncParameters();
        $this->validateParameters($params);
        $syncLogService = $this->getSyncLogService();

        $syncLogEntity = new SyncLogEntity(
            (int) $params[SyncLogServiceInterface::PARAM_SYNC_ID],
            $params[SyncLogServiceInterface::PARAM_BOX_ID],
            $params[SyncLogServiceInterface::PARAM_ORGANIZATION_ID],
            [],
            SyncLogEntity::STATUS_IN_PROGRESS,
            Report::createInfo('Synchronization started...'),
            new DateTime()
        );
        $syncLogEntity->setClientState([
            SyncLogServiceInterface::PARAM_VM_VERSION => $params[SyncLogServiceInterface::PARAM_VM_VERSION]
        ]);

        return $syncLogService->create($syncLogEntity);
    }

    /**
     * @param AbstractSyncEvent $event
     * @return SyncLogEntity
     *
     * @throws common_exception_Error
     */
    private function getSyncLogEntityForEvent(AbstractSyncEvent $event)
    {
        try {
            $params = $event->getSyncParameters();
            $this->validateParameters($params);

            return $this->findSyncLogEntity($params[SyncLogServiceInterface::PARAM_SYNC_ID], $params[SyncLogServiceInterface::PARAM_BOX_ID]);
        } catch (SyncLogEntityNotFound $e) {
            return $this->createSyncLogRecord($event);
        }
    }

    /**
     * @param string $syncId
     * @param string $boxId
     * @return SyncLogEntity
     *
     * @throws SyncLogEntityNotFound
     * @throws common_exception_Error
     */
    private function findSyncLogEntity($syncId, $boxId)
    {
        $syncLogService = $this->getSyncLogService();

        return $syncLogService->getBySyncIdAndBoxId($syncId, $boxId);
    }


    /**
     * @param SyncFinishedEvent $event
     */
    public function logSyncFinished(SyncFinishedEvent $event)
    {
        try {
            $syncLogEntity = $this->getSyncLogEntityForEvent($event);
            $params = $event->getSyncParameters();

            $syncLogService = $this->getSyncLogService();
            $eventReport = $event->getReport();
            if (isset($params[SyncLogServiceInterface::PARAM_CLIENT_STATE])) {
                $clientState = $params[SyncLogServiceInterface::PARAM_CLIENT_STATE];
                $clientStateReport = \common_report_Report::jsonUnserialize($clientState);
                $syncLogEntity->setClientState($this->parseClientState($params, $clientStateReport));
                $eventReport->add($clientStateReport);
            }

            $report = $syncLogEntity->getReport();
            $report->add($eventReport);
            if ($report->containsError()) {
                $syncLogEntity->setFailed();
            } else {
                $syncLogEntity->setCompleted();
            }
            $syncLogEntity->setFinishTime(new DateTime());

            $syncLogService->update($syncLogEntity);
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * @param SyncFailedEvent $event
     */
    public function logSyncFailed(SyncFailedEvent $event)
    {
        try {
            $syncLogEntity = $this->getSyncLogEntityForEvent($event);

            $syncLogEntity->setFailed();
            $syncLogEntity->setFinishTime(new DateTime());

            $this->updateSyncLogRecord($syncLogEntity, $event);
        } catch (Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    /**
     * @param array $params
     * @return bool
     */
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
        if (empty($params[SyncLogServiceInterface::PARAM_VM_VERSION])) {
            throw new \InvalidArgumentException('Required synchronization parameter is missing: ' . SyncLogServiceInterface::PARAM_VM_VERSION);
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

    /**
     * @return SyncLogClientStateParser
     */
    private function getSyncLogClientStateParser()
    {
        return $this->getServiceLocator()->get(SyncLogClientStateParser::SERVICE_ID);
    }

    /**
     * @param array $params
     * @param Report $clientStateReport
     * @return array
     */
    private function parseClientState(array $params, $clientStateReport)
    {
        $clientState = $this->getSyncLogClientStateParser()->parse($clientStateReport);
        if (isset($params[SyncLogServiceInterface::PARAM_VM_VERSION])) {
            $clientState[SyncLogServiceInterface::PARAM_VM_VERSION] = $params[SyncLogServiceInterface::PARAM_VM_VERSION];
        }

        return $clientState;
    }
}
