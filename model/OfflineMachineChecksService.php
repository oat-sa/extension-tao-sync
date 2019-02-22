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
 * Copyright (c) 2019. (original work) Open Assessment Technologies SA;
 */

namespace oat\taoSync\model;


use common_report_Report as Report;
use Exception;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\event\AbstractSyncEvent;
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

class OfflineMachineChecksService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/OfflineMachineChecksService';
    const OPTION_CHECKS = 'checks';

    const REPORT_USAGE_TITLE = 'Offline machine usage report';
    private $checkServices = [];

    /**
     * @param AbstractSyncEvent $event
     * @throws \oat\oatbox\service\exception\InvalidService
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function listen(AbstractSyncEvent $event)
    {
        if ($this->getCheckServices() && in_array(get_class($event), [SyncFinishedEvent::class, SyncFailedEvent::class], true)) {

            try {
                $params = $event->getSyncParameters();
                $syncLogService = $this->getServiceLocator()->get(SyncLogServiceInterface::SERVICE_ID);
                /** @var SyncLogEntity $syncLogEntity */
                $syncLogEntity = $syncLogService->getBySyncIdAndBoxId($params[SyncLogServiceInterface::PARAM_SYNC_ID], $params[SyncLogServiceInterface::PARAM_BOX_ID]);

                $syncReport = $syncLogEntity->getReport();
                $offlineMachineUsageReport = $this->getReport();
                $syncReport->add($offlineMachineUsageReport);
                $syncLogEntity->setReport($syncReport);

                $syncLogService->update($syncLogEntity);
            } catch (Exception $e) {
                $this->logError($e->getMessage());
            }
        }
    }

    /**
     * @return Report
     * @throws \common_exception_Error
     * @throws \oat\oatbox\service\exception\InvalidService
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    protected function getReport()
    {
        $dataUsageReport = new Report(Report::TYPE_INFO);
        $dataUsageReport->setMessage(self::REPORT_USAGE_TITLE);

        foreach ($this->getCheckServices() as $check) {
            $dataUsageReport->add($check->getReport());
        }

        return $dataUsageReport;
    }


    /**
     * @return MachineUsageStatsInterface[]
     * @throws \oat\oatbox\service\exception\InvalidService
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function getCheckServices()
    {
        if (empty($this->checkServices)) {
            $subs = $this->getOption(self::OPTION_CHECKS);
            foreach ($subs as $sub) {
                $this->checkServices[] = $this->buildService($sub);
            }
        }
        return $this->checkServices;
    }

}