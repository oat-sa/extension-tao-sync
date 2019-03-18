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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\SyncLog;

use common_report_Report as Report;
use oat\oatbox\service\ConfigurableService;

/**
 * Class SyncLogClientStateParser
 * @package oat\taoSync\model\SyncLog
 */
class SyncLogClientStateParser extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncLogClientStateParser';

    private $parsedData;

    /**
     * @param Report $report
     * @return array
     */
    public function parse(Report $report)
    {
        $this->parsedData = [];

        $this->extractClientState($report);

        return $this->parsedData;
    }

    /**
     * @param Report $report
     */
    private function extractClientState(Report $report)
    {
        if ($report->hasChildren()) {
            foreach ($report->getIterator() as $child) {
                $this->extractClientState($child);
            }
        }

        $reportData = $report->getData() ? $report->getData() : [];

        foreach ($reportData as $key => $value) {
            $this->parsedData[$key] = $value;
        }
    }
}
