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
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\scripts\tool\Export\ExportSynchronizationPackage;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;

class Exporter extends \tao_actions_CommonModule
{
    use TaskLogActionTrait;

    const TASK_LABEL = 'Export Synchronization Package';

    /**
     * Create a task
     */
    public function createTask()
    {
        try {
            $parameters = $this->prepareTaskParameters();
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

        return $queueService->createTask($callable, $parameters, self::TASK_LABEL);
    }

    private function prepareTaskParameters()
    {
        $requestData = $this->getRequestParameters();
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
}