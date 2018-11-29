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

use oat\oatbox\service\ConfigurableService;
use oat\oatbox\user\User;
use oat\tao\model\datatable\DatatableRequest;
use oat\tao\model\taskQueue\TaskLog;
use oat\tao\model\taskQueue\TaskLog\Broker\TaskLogBrokerInterface;
use oat\tao\model\taskQueue\TaskLog\DataTablePayload;
use oat\tao\model\taskQueue\TaskLog\TaskLogFilter;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeAll;
/**
 * Class SynchronizationHistoryService
 * @package oat\taoSync\model\SynchronizationHistory
 */
class SynchronizationHistoryService extends ConfigurableService implements SynchronizationHistoryServiceInterface
{
    const OPTION_PAYLOAD_FORMATTER = 'payload_formatter';

    /**
     * Return synchronization history payload
     *
     * @param User $user
     * @param DatatableRequest $request
     * @return array
     */
    public function getSyncHistory(User $user, DatatableRequest $request) {
        /** @var TaskLog $taskLogService */
        $taskLogService = $this->getServiceLocator()->get(TaskLogInterface::SERVICE_ID);
        $filter = $this->getFilter($user);

        $payload = $taskLogService->getDataTablePayload($filter, $request);
        $this->addPayloadCustomization($payload);

        return $payload->getPayload();
    }

    /**
     * Register callback function to customize payload row.
     *
     * @param DataTablePayload $payload
     */
    private function addPayloadCustomization(DataTablePayload $payload) {
        $historyFormatter = $this->getServiceLocator()->get(HistoryPayloadFormatterInterface::SERVICE_ID);

        $payload->customiseRowBy(function () use ($historyFormatter) {
            return $historyFormatter->format($this);
        }, true);
    }

    /**
     * @param User $user
     * @return TaskLogFilter
     */
    private function getFilter(User $user) {
        $filter = new TaskLogFilter();
        $filter->addFilter(TaskLogBrokerInterface::COLUMN_TASK_NAME, TaskLogFilter::OP_EQ, SynchronizeAll::class);
        $filter->addFilter(TaskLogBrokerInterface::COLUMN_OWNER, TaskLogFilter::OP_EQ, $user->getIdentifier());

        return $filter;
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
