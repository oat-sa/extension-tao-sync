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

use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\taoSync\scripts\tool\Import\ImportSynchronizationPackage;

class Importer extends \tao_actions_CommonModule
{
    use TaskLogActionTrait;

    const TASK_LABEL = 'Import Synchronization Package';
    const PARAM_PACKAGE_NAME = 'syncPackage';

    /**
     * Create a task
     */
    public function createTask()
    {
        try {
            $this->validateRequest();
            $task = $this->createImportTask();
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
    private function createImportTask()
    {
        $request = $this->getPsrRequest();
        /** @var \GuzzleHttp\Psr7\UploadedFile $file */
        $file = $request->getUploadedFiles()[static::PARAM_PACKAGE_NAME];

        $action = new ImportSynchronizationPackage();
        $this->propagate($action);
        $parameters = $action->prepare($file);

        $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        $task = $queueService->createTask($action, $parameters, self::TASK_LABEL);

        return $task;
    }

    /**
     * @throws \common_exception_BadRequest
     * @throws \common_exception_MethodNotAllowed
     */
    private function validateRequest()
    {
        $request = $this->getPsrRequest();
        if ($request->getMethod() !== 'POST') {
            throw new \common_exception_MethodNotAllowed('Only POST requests are allowed.');
        }
        $files = $request->getUploadedFiles();
        if (!isset($files[static::PARAM_PACKAGE_NAME])) {
            throw new \common_exception_BadRequest(__('Missed package file'));
        }
    }
}
