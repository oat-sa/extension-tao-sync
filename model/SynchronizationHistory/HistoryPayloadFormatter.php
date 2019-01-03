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
use oat\taoSync\model\SyncLogStorageInterface;

/**
 * Class HistoryOutputFormatter
 * @package oat\taoSync\model\SynchronizationHistory
 */
class HistoryPayloadFormatter extends ConfigurableService implements HistoryPayloadFormatterInterface
{
    const OPTION_DATA_MODEL = 'dataModel';

    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if (!$this->hasOption(self::OPTION_DATA_MODEL)) {
            throw new MissingOptionException(self::OPTION_DATA_MODEL . ' is required option.', self::OPTION_DATA_MODEL);
        }
    }

    /**
     * @return array
     */
    public function getDataModel()
    {
        return $this->getOption(self::OPTION_DATA_MODEL);
    }

    /**
     * @param array $data
     * @return array
     */
    public function format(array $data)
    {
        $output = [
            'id' => $data[SyncLogStorageInterface::COLUMN_ID],
            'status' => $data[SyncLogStorageInterface::COLUMN_STATUS],
            'created_at' => $data[SyncLogStorageInterface::COLUMN_STARTED_AT],
            'finished_at' => $data[SyncLogStorageInterface::COLUMN_FINISHED_AT],
            'organisation' => $data[SyncLogStorageInterface::COLUMN_ORGANIZATION_ID],
            'box_id' => $data[SyncLogStorageInterface::COLUMN_BOX_ID],
            'data' => $this->parseSyncDetails(json_decode($data[SyncLogStorageInterface::COLUMN_DATA], true))
        ];

        return $output;
    }

    /**
     * @param array $syncData
     * @return string
     */
    private function parseSyncDetails(array $syncData)
    {
        $messages = [];

        foreach ($syncData as $entityType => $entityData) {
            $message = "{$entityType}: ";
            foreach ($entityData as $action => $amount) {
                $message .= "{$amount} {$action}; ";
            }
            $messages[] = $message;
        }

        return empty($messages) ? 'No synchronized data' : implode(PHP_EOL, $messages);
    }
}
