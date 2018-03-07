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

use oat\taoSync\scripts\tool\synchronisation\SynchronizeAll;
use oat\taoSync\model\ui\FormFieldsService;
use oat\taoTaskQueue\model\QueueDispatcherInterface;
use oat\taoTaskQueue\model\TaskLogActionTrait;

class Synchronizer extends \tao_actions_CommonModule
{
    use TaskLogActionTrait;

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
        try{
            $data  = $this->getRequestParameters();

            $label = $data['label'];
            unset($data['label']);

            $callable = $this->propagate(new SynchronizeAll());
            $queueService = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
            return $this->returnTaskJson($queueService->createTask($callable, $data, $label));
        } catch(\Exception $e){
            return $this->returnJson([
                'success'   => false,
                'errorMsg'  => $e->getMessage(),
                'errorCode' => $e->getCode()
            ]);
        }
    }

    /**
     * @return FormFieldsService
     */
    protected function getFormFieldsService()
    {
        return $this->getServiceLocator()->get(FormFieldsService::SERVICE_ID);
    }
}
