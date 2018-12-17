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


use oat\oatbox\service\ConfigurableService;
use common_report_Report as Report;

/**
 * Class SyncLogDataParser
 * @package oat\taoSync\model\SyncLog
 */
class SyncLogDataParser extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncLogDataParser';

    private $parsedData = [];

    /**
     * @param Report $report
     * @return array
     */
    public function parseSyncData(Report $report) {
        $this->parsedData = [];

        $this->parseSynchronizedEntities($report);

        return $this->parsedData;
    }

    /**
     * @param Report $report
     */
    private function parseSynchronizedEntities(Report $report) {
        if ($report->hasChildren()) {
            foreach ($report->getIterator() as $child) {
                $this->parseSynchronizedEntities($child);
            }
        }

        $data = $report->getData() ? $report->getData() : [];

        // @todo: merge data
        $this->parsedData = $data;


//        if (strpos($message, 'entities created.')) {
//            $this->messages[] = trim($message, '.');
//        }
//
//        if ($position = strpos($message, 'exports have been acknowledged')) {
//            $message = substr($message, 0, $position - 1);
//            list($amount, $entity) = explode(' ', $message, 2);
//
//            $this->entities[$entity] = isset($this->entities[$entity]) ? $this->entities[$entity] + $amount : $amount;
//        }
    }
}
