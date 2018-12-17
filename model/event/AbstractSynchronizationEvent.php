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

namespace oat\taoSync\model\event;

use common_report_Report as Report;
use oat\oatbox\event\Event;

abstract class AbstractSynchronizationEvent implements Event
{
    /**
     * @var array
     */
    private $syncParameters = [];

    /**
     * @var Report
     */
    private $report = null;

    /**
     * SynchronizationStarted constructor.
     * @param $syncParameters
     * @param Report $report
     */
    public function __construct($syncParameters, Report $report)
    {
        $this->syncParameters = $syncParameters;
        $this->report = $report;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return static::class;
    }

    /**
     * @return array
     */
    public function getSyncParameters()
    {
        return $this->syncParameters;
    }

    /**
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
    }
}
