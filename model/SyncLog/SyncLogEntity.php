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
use InvalidArgumentException;
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

    /** @var array */
    private $clientState;

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
     * @param DateTime $finishTime
     * @param integer|null $id
     */
    public function __construct($syncId, $boxId, $organizationId, array $data, $status, Report $report, DateTime $startTime, DateTime $finishTime = null, $id = null)
    {
        if (!is_int($id) && $id !== null) {
            throw new InvalidArgumentException('Invalid value for "id" parameter provided.');
        }

        if (!is_int($syncId)) {
            throw new InvalidArgumentException('Invalid value for "syncId" parameter provided.');
        }

        if (!is_string($boxId)) {
            throw new InvalidArgumentException('Invalid value for "boxId" parameter provided.');
        }

        if (!is_string($organizationId)) {
            throw new InvalidArgumentException('Invalid value for "organizationId" parameter provided.');
        }

        if (!is_string($status)) {
            throw new InvalidArgumentException('Invalid value for "status" parameter provided.');
        }

        $this->id = $id;
        $this->syncId = $syncId;
        $this->boxId = $boxId;
        $this->organizationId = $organizationId;
        $this->data = $data;
        $this->status = $status;
        $this->report = $report;
        $this->clientState = [];
        $this->startTime = $startTime;
        $this->finishTime = $finishTime;
    }

    /**
     * @return int|null
     */
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
     * @return array
     */
    public function getClientState()
    {
        return $this->clientState;
    }

    /**
     * @param array $clientState
     */
    public function setClientState(array $clientState)
    {
        $this->clientState = $clientState;
    }

    /**
     * @return DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return DateTime|null
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
        if ($finishTime < $this->startTime) {
            throw new InvalidArgumentException('Finish time can not be smaller than start time.');
        }

        $this->finishTime = $finishTime;
    }
}
