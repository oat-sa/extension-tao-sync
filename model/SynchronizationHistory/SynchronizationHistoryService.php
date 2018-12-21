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

namespace oat\taoSync\model\SynchronizationHistory;

use oat\oatbox\extension\script\MissingOptionException;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\tao\model\datatable\DatatableRequest;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\SyncLogStorageInterface;
use oat\taoSync\model\SyncLog\SyncLogFilter;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;
use oat\taoSync\model\SyncLog\Payload\DataTablePayload;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;

/**
 * Class SynchronizationHistoryService
 * @package oat\taoSync\model\SynchronizationHistory
 */
class SynchronizationHistoryService extends ConfigurableService implements SynchronizationHistoryServiceInterface
{
    /**
     * Return synchronization history payload
     *
     * @param User $user
     * @param DatatableRequest $request
     * @return array
     */
    public function getSyncHistory(User $user, DatatableRequest $request)
    {
        $filter = new SyncLogFilter();
        $this->setBoxIdFilter($filter);
        // @TODO: Set user filters.

        /** @var SyncLogServiceInterface $syncLogService */
        $syncLogService = $this->getServiceLocator()->get(SyncLogServiceInterface::SERVICE_ID);
        $payload = new DataTablePayload($filter, $request, $syncLogService);
        $this->setPayloadCustomizer($payload);

        return $payload->getPayload();
    }

    /**
     * Filter payload data be box id if available.
     */
    private function setBoxIdFilter(SyncLogFilter $filter)
    {
        $boxId = $this->getServiceLocator()->get(PublishingService::SERVICE_ID)->getBoxIdByAction(SynchronizeData::class);

        if (!empty($boxId)) {
            $filter->eq(SyncLogStorageInterface::COLUMN_BOX_ID, $boxId);
        }
    }

    /**
     * Register callback function to customize payload row.
     *
     * @param DataTablePayload $payload
     */
    private function setPayloadCustomizer(DataTablePayload $payload) {
        $historyFormatter = $this->getServiceLocator()->get(HistoryPayloadFormatterInterface::SERVICE_ID);

        $payload->customiseRowBy(function ($row) use ($historyFormatter) {
            return $historyFormatter->format($row);
        }, true);
    }



    /**
     * @param User $user
     * @param $id
     * @return \JsonSerializable
     * @throws \common_exception_NotFound
     */
    public function getSyncReport(User $user, $id) {
        /** @var TaskLog $taskLogService */
        $taskLogService = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);
        $taskLogEntity = $taskLogService->getByIdAndUser($id, $user->getIdentifier());

        return $taskLogEntity->getReport();
    }
}
