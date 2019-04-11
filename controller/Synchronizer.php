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
use oat\tao\model\security\xsrf\TokenService;
use oat\tao\model\service\ApplicationService;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\tao\model\taskQueue\TaskLog\Entity\EntityInterface;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoSync\model\Exception\NotSupportedVmVersionException;
use oat\taoSync\model\Execution\DeliveryExecutionStatusManager;
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\OfflineMachineChecksService;
use oat\taoSync\model\Parser\DeliveryExecutionContextParser;
use oat\taoSync\model\SynchronizeAllTaskBuilderService;
use oat\taoSync\model\SyncService;
use oat\taoSync\model\ui\FormFieldsService;
use oat\taoSync\model\VirtualMachine\VmVersionChecker;

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

        $dashboardUrl = _url('index', 'Main', 'tao', [
            'structure' => 'tools',
            'ext' => 'taoSync',
            'section' => 'sync-history'
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
            $data = $this->getRequestParameters();
            $label = $data['label'];
            unset($data['label']);

            $this->checkSyncPreconditions();
            $task = $this->createSyncTask($data, $label);

            return $this->returnTaskJson($task);
        } catch (NotSupportedVmVersionException $e) {
            $this->createSyncTask($data, $label);

            return $this->returnJson([
                'success' => false,
                'errorMsg' => $e->getMessage(),
                'errorCode' => $e->getCode()
            ]);
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

    public function getMachineChecks()
    {
        $this->setData('includeExtension', self::EXTENSION_ID);

        $data = [
            $this->getVersionInfo(),
            $this->getDiskSpaceStatistics(),
            $this->getConnectivityStatistics(),
        ];

        return $this->returnJson([
            'success' => true,
            'data' => array_values(array_filter($data)),
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

        $data = [];
        $activeSessionsData = [];
        if ($syncService->getOption(SyncService::OPTION_CHECK_ACTIVE_SESSIONS)) {
            $serviceLocator = $this->getServiceLocator();
            $activeSessions = $serviceLocator->get(DeliveryExecutionStatusManager::SERVICE_ID)->getExecutionsInProgress();
            $activeSessionsData = $serviceLocator->get(DeliveryExecutionContextParser::SERVICE_ID)->parseExecutionContextDetails($activeSessions);

            /** @var TokenService $tokenService */
            $tokenService = $serviceLocator->get(TokenService::SERVICE_ID);
            $data['token'] = [
                'name' => $tokenService->getTokenName(),
                'token' => $tokenService->createToken(),
            ];
        }

        $data['activeSessionsData'] = $activeSessionsData;

        return $this->returnJson([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * @return array
     */
    private function getVersionInfo()
    {
        $currentVmVersion = $this->getServiceLocator()->get(ApplicationService::SERVICE_ID)->getPlatformVersion();
        $vmVersionChecker = $this->getServiceLocator()->get(VmVersionChecker::SERVICE_ID);

        return [
            'title' => __('Virtual machine version'),
            'score' => $vmVersionChecker->isVmSupported($currentVmVersion) ? 100 : 0,
            'info'  => [
                [
                    'text' => __('Version')  .': ' . $currentVmVersion,
                ],
            ]
        ];
    }

    /**
     * @return array|null
     */
    private function getDiskSpaceStatistics()
    {
        /** @var \common_report_Report $reports */
        $reports = $this->getServiceLocator()->get(OfflineMachineChecksService::SERVICE_ID)->getReport();
        $freePercent = [];
        $info = [];

        foreach ($reports as $report) {
            $diskSpaceValue = current($report->getData());
            $total = $diskSpaceValue['used'] + $diskSpaceValue['free'];
            $freePercent[] = $diskSpaceValue['free'] / ($total / 100);
            $info[] = ['text' => $report->getMessage()];
        }

        if (empty($reports->getChildren())) {
            return null;
        }

        $result = [
            'title' => __('Disk & DB space:'),
            'score' => floor(min($freePercent)),
            'info'  => $info
        ];

        return $result;
    }

    /**
     * @return array
     */
    private function getConnectivityStatistics()
    {
        return [
            'title' => __('Connectivity'),
            'score' => 32,
            'info'  => [
                ['text' => __('Download') .' : 80 MBit/s'],
                ['text' => __('Upload') .' : 72 MBit/s'],
            ]
        ];
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

    /**
     * @throws \common_exception_RestApi
     * @throws \core_kernel_persistence_Exception
     * @throws NotSupportedVmVersionException
     */
    private function checkSyncPreconditions()
    {
        $lastTask = $this->getLastSyncTask();
        if ($lastTask && !in_array($lastTask->getStatus(), ['completed', 'failed'])) {
            throw new \common_exception_RestApi(__('The synchronisation is already running!'), 423);
        }

        $currentVmVersion = $this->getServiceLocator()->get(ApplicationService::SERVICE_ID)->getPlatformVersion();
        $vmVersionChecker = $this->getServiceLocator()->get(VmVersionChecker::SERVICE_ID);

        if (!$vmVersionChecker->isVmSupported($currentVmVersion)) {
            throw new NotSupportedVmVersionException(__('Current version of Tao Local is not compatible with Tao Cloud.'), 409);
        }
    }

    /**
     * @param $data
     * @param $label
     * @return TaskInterface
     * @throws \common_exception_Error
     */
    private function createSyncTask($data, $label)
    {
        /** @var SynchronizeAllTaskBuilderService $syncAllRunnerService */
        $syncAllRunnerService = $this->getServiceLocator()->get(SynchronizeAllTaskBuilderService::SERVICE_ID);
        $task = $syncAllRunnerService->run($data, $label);
        $this->setLastSyncTask($task);
        return $task;
    }
}
