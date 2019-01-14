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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\controller;

use oat\oatbox\session\SessionService;
use oat\tao\model\datatable\implementation\DatatableRequest;
use oat\taoSync\model\SynchronizationHistory\HistoryPayloadFormatterInterface;
use oat\taoSync\model\SynchronizationHistory\SynchronizationHistoryServiceInterface;
use tao_actions_CommonModule;

/**
 * Class SynchronizationHistory
 * @package oat\taoSync\controller
 */
class SynchronizationHistory extends tao_actions_CommonModule
{
    /**
     * Render submitted transmissions table
     */
    public function index()
    {
        $this->setData('config', [
            'dataModel' => $this->getServiceLocator()->get(HistoryPayloadFormatterInterface::SERVICE_ID)->getDataModel()
        ]);

        $this->setView('sync/history.tpl', 'taoSync');
    }

    /**
     * Return synchronization history payload
     *
     * @return string
     * @throws \common_exception_Error
     */
    public function getHistory()
    {
        /** @var SynchronizationHistoryServiceInterface $syncHistoryService */
        $syncHistoryService = $this->getServiceLocator()->get(SynchronizationHistoryServiceInterface::SERVICE_ID);

        $user = $this->getServiceLocator()->get(SessionService::SERVICE_ID)->getCurrentUser();
        $request = DatatableRequest::fromGlobals();
        $payload = $syncHistoryService->getSyncHistory($user, $request);

        $this->returnJson($payload);
    }

    /**
     * Return detailed synchronisation report.
     *
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     */
    public function viewReport() {
        if (!$this->hasRequestParameter('id')) {
            throw new \common_exception_MissingParameter('id');
        }
        $id = $this->getRequestParameter('id');
        $syncHistoryService = $this->getServiceLocator()->get(SynchronizationHistoryServiceInterface::SERVICE_ID);

        $this->returnJson($syncHistoryService->getSyncReport($id)->toArray());
    }
}
