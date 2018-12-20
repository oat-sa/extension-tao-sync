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

use DateTime;
use common_report_Report as Report;

/**
 * Class SyncLogEntity
 * @package oat\taoSync\model\SyncLog
 */
class SyncLogEntity
{
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    const STATUS_SUCCESS = 'Success';
    const STATUS_IN_PROGRESS = 'In progress';
    const STATUS_FAILED = 'Failed';

    /**
     * @var integer|null
     */
    private $id = null;

    /** @var integer */
    private $syncId;

    /** @var string */
    private $boxId;

    /** @var string */
    private $organizationId;

    /** @var array */
    private $data;

    /** @var string */
    private $status;

    /** @var Report */
    private $report;

    /** @var DateTime */
    private $startTime;

    /** @var DateTime */
    private $finishTime;

    /**
     * SyncLogEntity constructor.
     * @param integer $syncId
     * @param string $boxId
     * @param string $organizationId
     * @param array $data
     * @param string $status
     * @param Report $report
     * @param DateTime $startTime
     */
    public function __construct($syncId, $boxId, $organizationId, array $data, $status, Report $report, DateTime $startTime, $id = null)
    {
        $this->id = $id;
        $this->syncId = $syncId;
        $this->boxId = $boxId;
        $this->organizationId = $organizationId;
        $this->data = $data;
        $this->status = $status;
        $this->report = $report;
        $this->startTime = $startTime;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSyncId()
    {
        return $this->syncId;
    }

    /**
     * @return string
     */
    public function getBoxId()
    {
        return $this->boxId;
    }

    /**
     * @return string
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set synchronization status as completed.
     */
    public function setCompleted()
    {
        $this->status = self::STATUS_SUCCESS;
    }

    /**
     * Set synchronization status as failed.
     */
    public function setFailed()
    {
        $this->status = self::STATUS_FAILED;
    }

    /**
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
    }

    public function setReport(Report $report)
    {
        $this->report = $report;
    }

    /**
     * @return DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return DateTime
     */
    public function getFinishTime()
    {
        return $this->finishTime;
    }

    /**
     * @param DateTime $finishTime
     */
    public function setFinishTime(DateTime $finishTime)
    {
        $this->finishTime = $finishTime;
    }
}
