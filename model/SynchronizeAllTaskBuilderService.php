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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model;

use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\taoEncryption\Service\Session\EncryptedUser;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeAll;

class SynchronizeAllTaskBuilderService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SynchronizeAllTaskBuilder';

    const OPTION_TASKS_TO_RUN_ON_SYNC = 'tasksToRunOnSync';

    /**
     * @param $data
     * @param $label
     * @return TaskInterface
     * @throws \common_exception_Error
     */
    public function run($data, $label)
    {
        $data['applicationKey'] = $this->getApplicationKey();
        $data['actionsToRun']   = $this->getOption(static::OPTION_TASKS_TO_RUN_ON_SYNC);

        $syncAll = new SynchronizeAll();
        $callable = $this->propagate($syncAll);

        /** @var QueueDispatcherInterface $queueService */
        $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        $task = $queueService->createTask($callable, $data, $label);

        return $task;
    }

    /**
     * @return string
     * @throws \common_exception_Error
     */
    protected function getApplicationKey()
    {
        $user = \common_session_SessionManager::getSession()->getUser();

        if ($user instanceof EncryptedUser){
            return $user->getApplicationKey();
        }

        return '';
    }
}