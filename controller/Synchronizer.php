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

use oat\oatbox\service\ServiceManager;
use oat\taoTaskQueue\model\QueueDispatcherInterface;

class Synchronizer extends \tao_actions_CommonModule
{

    const EXTENSION_ID = 'taoSync';

    /**
     * Entry page.
     */
    public function index()
    {
        $this->setData('form-fields', $this->getFormFields());
        $this->setView('sync/index.tpl', 'taoSync');
    }

    public function createTask() {
        $queueService = ServiceManager::getServiceManager()->get(QueueDispatcherInterface::SERVICE_ID);
        $task = $queueService->createTask($this, [], 'Synchronize Data');
        if ($task->isEnqueued()) {
            echo 'Successfully published';
        }
    }


    /**
     * Retrieve custom form fields
     *
     * @return array
     */
    protected function getFormFields() {
        $defaults = [
            'element' => 'input',
            'attributes' => []
        ];
        $extension = $this->getServiceManager()
                          ->get(\common_ext_ExtensionsManager::SERVICE_ID)
                          ->getExtensionById(self::EXTENSION_ID);
        $formFields = array_filter((array)$extension->getConfig('formFields'));

        foreach($formFields as $key => &$formField) {
            $formField = array_merge($defaults, $formField);
            if(empty($formField['attributes']['name'])) {
                $formField['attributes']['name'] = $key;
            }
            if(empty($formField['attributes']['id'])) {
                $formField['attributes']['id'] = $key;
            }
        }
        return $formFields;
    }
}