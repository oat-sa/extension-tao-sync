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
 * Copyright (c) 2019 Open Assessment Technologies SA
 */

namespace oat\taoSync\controller;

use oat\tao\model\service\ApplicationService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\TaskLog\Entity\EntityInterface;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\Exception\SyncExportException;
use oat\taoSync\model\Export\ExportService;
use oat\taoSync\scripts\tool\Export\ExportSynchronizationPackage;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;

class Exporter extends \tao_actions_CommonModule
{
    use TaskLogActionTrait;

    const TASK_LABEL = 'Export Synchronization Package';

    const EXPORT_TASK_CACHE_KEY = 'export_task_id';

    /**
     * Create a task
     */
    public function createTask()
    {
        try {
            $parameters = $this->prepareTaskParameters();
            $this->checkExportPreconditions($parameters['organisation_id']);
            $task = $this->createExportTask($parameters);

            return $this->returnTaskJson($task);
        } catch (\common_Exception $e) {
            return $this->returnJson([
                'success' => false,
                'errorMsg' => $e->getMessage(),
                'errorCode' => $e->getCode()
            ]);
        }
    }

    /**
     * @param $parameters
     * @return TaskInterface
     * @throws \common_exception_Error
     */
    private function createExportTask(array $parameters)
    {
        $exportPackage = new ExportSynchronizationPackage();
        $callable = $this->propagate($exportPackage);

        /** @var QueueDispatcherInterface $queueService */
        $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);

        $task = $queueService->createTask($callable, $parameters, self::TASK_LABEL);
        $this->setLastExportTask($task, $parameters['organisation_id']);

        return $task;
    }

    private function prepareTaskParameters()
    {
        $request = $this->getPsrRequest();
        $requestData = $request->getQueryParams();
        $this->validateRequestParameters($requestData);

        return [
            'organisation_id' => $requestData['organisation_id'],
            'tao_version' => $this->getApplicationService()->getPlatformVersion(),
            'box_id' => $this->getBoxId(),
        ];
    }

    private function validateRequestParameters($data)
    {
        if (empty($data['organisation_id'])) {
            throw new \common_exception_BadRequest('Parameter "organisation_id" is required.');
        }
    }

    /**
     * @return string
     */
    private function getBoxId()
    {
        return $this->getServiceLocator()->get(PublishingService::SERVICE_ID)->getBoxIdByAction(SynchronizeData::class);
    }

    /**
     * @return ApplicationService
     */
    private function getApplicationService()
    {
        return $this->getServiceLocator()->get(ApplicationService::SERVICE_ID);
    }

    /**
     * @throws \common_exception_RestApi
     * @throws SyncExportException
     */
    private function checkExportPreconditions($orgId)
    {
        /** @var ExportService $exportService */
        $exportService = $this->getServiceLocator()->get(ExportService::SERVICE_ID);
        if (!$exportService->getOption(ExportService::OPTION_IS_ENABLED)) {
            throw new SyncExportException(__('Synchronization export feature is not enabled.'));
        }
        $processedTaskStatusList = [
            TaskLogInterface::STATUS_COMPLETED,
            TaskLogInterface::STATUS_FAILED,
            TaskLogInterface::STATUS_ARCHIVED,
            TaskLogInterface::STATUS_CANCELLED
        ];
        $lastTask = $this->getLastExportTask($orgId);
        if ($lastTask && !in_array($lastTask->getStatus(), $processedTaskStatusList)) {
            throw new \common_exception_RestApi(__('The export is already running!'), 423);
        }
    }

    /**
     * @param $orgId
     * @return null|EntityInterface
     */
    private function getLastExportTask($orgId)
    {
        try {
            $taskId = $this->getCacheService()->get(self::EXPORT_TASK_CACHE_KEY . $orgId);
            return $this->getTaskLogEntity($taskId);
        } catch (\common_cache_NotFoundException $e) {
        } catch (\common_exception_NotFound $e) {
        }

        return null;
    }

    /**
     * @param $task
     * @param $orgId
     */
    private function setLastExportTask($task, $orgId)
    {
        $this->getCacheService()->put($task->getId(), self::EXPORT_TASK_CACHE_KEY . $orgId);
    }

    /**
     * @return \common_cache_Cache
     */
    private function getCacheService()
    {
        return $this->getServiceLocator()->get(\common_cache_Cache::SERVICE_ID);
    }
}
