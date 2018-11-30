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

use common_report_Report;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\TaskLog\Entity\TaskLogEntity;

/**
 * Class HistoryOutputFormatter
 * @package oat\taoSync\model\SynchronizationHistory
 */
class HistoryPayloadFormatter extends ConfigurableService implements HistoryPayloadFormatterInterface
{
    const TIME_FORMAT = 'd/m/Y h:i a';

    private $entities = [];
    private $messages = [];

    /**
     * @param TaskLogEntity $log
     * @return array
     */
    public function format(TaskLogEntity $log)
    {
        $parameters = $log->getParameters();
        $output = [
            'id' => $log->getId(),
            'status' => $log->getStatus()->getLabel(),
            'created_at' => $log->getCreatedAt()->format(self::TIME_FORMAT),
            'organisation' => $parameters['organisation_id'] ?: '',
            'data' => $this->parseSyncDetails($log->getReport())
        ];

        return $output;
    }

    /**
     * @param common_report_Report $report
     * @return string
     */
    private function parseSyncDetails(common_report_Report $report) {
        $this->entities = [];
        $this->messages = [];
        $this->parseSynchronizedEntities($report);

        foreach ($this->entities as $entity => $amount) {
            $this->messages[] = "{$amount} {$entity} synchronized";
        }

        return empty($this->messages) ? 'No synchronized data' : implode(PHP_EOL, $this->messages);
    }

    /**
     * @param common_report_Report $report
     */
    private function parseSynchronizedEntities(common_report_Report $report) {
        if ($report->hasChildren()) {
            foreach ($report->getIterator() as $child) {
                $this->parseSynchronizedEntities($child);
            }
        }

        $message = $report->getMessage();
        if (strpos($message, 'entities created.')) {
            $this->messages[] = trim($message, '.');
        }

        if ($position = strpos($message, 'exports have been acknowledged')) {
            $message = substr($message, 0, $position - 1);
            list($amount, $entity) = explode(' ', $message, 2);

            $this->entities[$entity] = isset($this->entities[$entity]) ? $this->entities[$entity] + $amount : $amount;
        }
    }
}
