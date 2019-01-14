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

namespace oat\taoSync\model\SyncLog;

/**
 * Class SyncLogDataHelper
 * @package oat\taoSync\model\SyncLog
 */
class SyncLogDataHelper
{
    /**
     * Merge synchronization log data.
     *
     * @param array $initialData
     * @param array $newData
     * @return array
     */
    public static function mergeSyncData($initialData = [], array $newData)
    {
        if (!is_array($initialData)) {
            $initialData = is_null($initialData) ? [] : [$initialData];
        }

        foreach ($newData as $entityName => $entityData) {
            if (!is_array($entityData)) {
                continue;
            }

            foreach ($entityData as $action => $amount) {
                $currentAmount = isset($initialData[$entityName][$action]) ? $initialData[$entityName][$action] : 0;

                $initialData[$entityName][$action] = $currentAmount + $amount;
            }
        }

        return $initialData;
    }
}
