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

namespace oat\taoSync\scripts\tool\synchronisationHistory;

use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\SynchronizationHistory\HistoryPayloadFormatter;
use oat\taoSync\model\SynchronizationHistory\HistoryPayloadFormatterInterface;
use oat\taoSync\model\SynchronizationHistory\ClientSynchronizationHistoryService;
use oat\taoSync\model\SynchronizationHistory\SynchronizationHistoryServiceInterface;

/**
 * Class SetupClientSynchronisationHistory
 * @package oat\taoSync\scripts\tool\synchronisationHistory
 */
class SetupClientSynchronisationHistory extends InstallAction
{
    public function __invoke($params)
    {
        $options = [
            HistoryPayloadFormatter::OPTION_DATA_MODEL => [
                'status' => [
                    'id' => 'status',
                    'label' => __('Result'),
                    'sortable' => true,
                ],
                'created_at' => [
                    'id' => 'created_at',
                    'label' => __('Started at'),
                    'sortable' => true,
                ],
                'finished_at' => [
                    'id' => 'finished_at',
                    'label' => __('Finished at'),
                    'sortable' => true,
                ],
                'data' => [
                    'id' => 'data',
                    'label' => __('Data'),
                    'sortable' => false,
                ],
                'organisation' => [
                    'id' => 'organisation',
                    'label' => __('Organisation ID'),
                    'sortable' => false,
                ],
            ]
        ];
        $payloadFormatter = new HistoryPayloadFormatter($options);
        $this->registerService(HistoryPayloadFormatterInterface::SERVICE_ID, $payloadFormatter);

        $synchronisationHistoryService = new ClientSynchronizationHistoryService([]);
        $this->registerService(SynchronizationHistoryServiceInterface::SERVICE_ID, $synchronisationHistoryService);

        return \common_report_Report::createSuccess('SynchronizationHistoryService successfully registered.');
    }
}
