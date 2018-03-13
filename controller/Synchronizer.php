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
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\ui\FormFieldsService;
use oat\taoSync\scripts\tool\SynchronizeData;
use oat\taoTaskQueue\model\QueueDispatcherInterface;
use oat\taoTaskQueue\model\Task\TaskInterface;
use oat\taoTaskQueue\model\TaskLogActionTrait;

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

            $callable = $this->propagate(new SynchronizeData());
            $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
            $task = $queueService->createTask($callable, $data, $label);
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
     * @param TaskInterface $task
     */
    protected function setLastSyncTask($task)
    {
        $this->getResource(DataSyncHistoryService::SYNCHRO_URI)->setPropertyValue($this->getProperty(DataSyncHistoryService::SYNCHRO_TASK), $task->getId());
    }

    /**
     * @return TaskInterface
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
}
