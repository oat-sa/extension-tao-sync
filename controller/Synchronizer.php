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
 * Copyright (c) 2018 Open Assessment Technologies SA
 */

namespace oat\taoSync\controller;

use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\tao\model\taskQueue\TaskLog\Entity\EntityInterface;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\OfflineMachineChecksService;
use oat\taoSync\model\SynchronizeAllTaskBuilderService;
use oat\taoSync\model\SyncService;
use oat\taoSync\model\ui\FormFieldsService;

class Synchronizer extends \tao_actions_CommonModule
{
    use TaskLogActionTrait;
    use OntologyAwareTrait;

    /**
     * Extension ID
     */
    const EXTENSION_ID = 'taoSync';

    /**
     * Entry page.
     */
    public function index()
    {
        $this->setData('form-fields', $this->getFormFieldsService()->getFormFields());
        $this->setData('form-action', _url('createTask'));
        $this->setData('includeTemplate', 'sync/extra.tpl');

        $this->injectExtraInfo();

        $dashboardUrl = _url('index', 'Main', 'tao', [
            'structure' => 'tools',
            'ext' => 'taoSync',
            'section' => 'sync-history',
        ]);

        $this->setData('dashboard-url', $dashboardUrl);

        $this->setView('sync/index.tpl', self::EXTENSION_ID);
    }

    /**
     * Create a task
     */
    public function createTask()
    {
        try {
            $lastTask = $this->getLastSyncTask();
            if ($lastTask && !in_array($lastTask->getStatus(), ['completed', 'failed'])) {
                throw new \common_exception_RestApi(__('The synchronisation is already running!'), 423);
            }

            $data = $this->getRequestParameters();

            $label = $data['label'];
            unset($data['label']);

            /** @var SynchronizeAllTaskBuilderService $syncAllRunnerService */
            $syncAllRunnerService = $this->getServiceLocator()->get(SynchronizeAllTaskBuilderService::SERVICE_ID);
            $task = $syncAllRunnerService->run($data, $label);
            $this->setLastSyncTask($task);

            return $this->returnTaskJson($task);
        } catch (\Exception $e) {
            return $this->returnJson([
                'success' => false,
                'errorMsg' => $e->getMessage(),
                'errorCode' => $e->getCode()
            ]);
        }
    }

    /**
     * Get the last task data
     * @return mixed
     * @throws \core_kernel_persistence_Exception
     */
    public function lastTask()
    {
        $task = $this->getLastSyncTask();
        $taskData = null;

        if ($task) {
            $taskData = $task->toArray();
        }

        return $this->returnJson([
            'success' => true,
            'data' => $taskData
        ]);
    }

    /**
     * Get count of active sessions
     * @return mixed
     * @throws \core_kernel_persistence_Exception
     */
    public function activeSessions()
    {
        /** @var SyncService $syncService */
        $syncService = $this->getServiceLocator()->get(SyncService::SERVICE_ID);

        $activeSessions = $syncService->getOption(SyncService::OPTION_CHECK_ACTIVE_SESSIONS)
            ? $this->getActiveSessions()
            : 0;

        return $this->returnJson([
            'success' => true,
            'data' => [
                'activeSessions' => $activeSessions
            ]
        ]);
    }

    /**
     * @param TaskInterface $task
     */
    protected function setLastSyncTask($task)
    {
        $this->getResource(DataSyncHistoryService::SYNCHRO_URI)->setPropertyValue($this->getProperty(DataSyncHistoryService::SYNCHRO_TASK), $task->getId());
    }

    /**
     * @return EntityInterface|null
     * @throws \core_kernel_persistence_Exception
     */
    protected function getLastSyncTask()
    {
        $taskId = $this->getResource(DataSyncHistoryService::SYNCHRO_URI)->getOnePropertyValue($this->getProperty(DataSyncHistoryService::SYNCHRO_TASK));
        if ($taskId) {
            try {
                return $this->getTaskLogEntity($taskId->uriResource);
            } catch (\common_exception_NotFound $e) {
            }
        }

        return null;
    }

    /**
     * @return FormFieldsService
     */
    protected function getFormFieldsService()
    {
        return $this->getServiceLocator()->get(FormFieldsService::SERVICE_ID);
    }

    /**
     * @return int
     */
    protected function getActiveSessions()
    {
        /** @var DeliveryMonitoringService $deliveryMonitoringService */
        $deliveryMonitoringService = $this->getServiceLocator()->get(DeliveryMonitoringService::SERVICE_ID);

        $deliveryExecutionsData = $deliveryMonitoringService->count([
            DeliveryMonitoringService::STATUS => [
                DeliveryExecution::STATE_ACTIVE
            ]
        ]);
        return $deliveryExecutionsData;
    }

    protected function injectExtraInfo()
    {
        $this->setData('includeExtension', self::EXTENSION_ID);
        $this->setData('extra', []);
        /** @var \common_report_Report $report */
        $report = $this->getServiceLocator()->get(OfflineMachineChecksService::SERVICE_ID)->getReport();
        $this->setData('extra', array_map(function (\common_report_Report $report) {
            return $report->getMessage();
        }, $report->getChildren()));
    }
}
