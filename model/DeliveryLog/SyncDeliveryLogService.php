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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model\DeliveryLog;

use oat\oatbox\service\ConfigurableService;
use \common_report_Report as Report;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\history\ResultSyncHistoryService;
use Psr\Log\LogLevel;

class SyncDeliveryLogService extends ConfigurableService implements SyncDeliveryLogServiceInterface
{
    const OPTION_CHUNK_SIZE = 'chunkSize';
    const DEFAULT_CHUNK_SIZE = 200;

    /** @var Report */
    protected $report;

    /** @var array */
    protected $syncStats;

    /**
     * @param array $params
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function synchronizeDeliveryLogs(array $params = [])
    {
        $this->report = Report::createInfo('Starting delivery log synchronisation...');

        $deliveryLogService = $this->getDeliveryLogService();
        $logsToSync = $this->getResultSyncHistory()->getResultsWithDeliveryLogNotSynced();

        $counter = 0;
        $logs = [];
        $stats = [];
        $statsOfLogs = [];

        foreach ($logsToSync as $resultId) {
            $deliveryLogs = $deliveryLogService->get($resultId);
            $logs[$resultId] = $deliveryLogs;
            $statsOfLogs[$resultId] = count($deliveryLogs);

            $this->report(count($deliveryLogs) . ' results logs found for result: ' . $resultId);
            $counter++;
            if ($counter % $this->getChunkSize() === 0) {
                $this->report($counter . ' results logs to send to remote server. Sending...', LogLevel::INFO);
                $this->sendDeliveryLogs($logs);
                $logs = [];
            }
        }

        if ($counter === 0) {
            $this->report('No result logs to synchronize', LogLevel::INFO);
        }

        if (!empty($logs)) {
            $stats[] = $this->sendDeliveryLogs($logs);
        }

        $logAsCompleted = [];
        foreach ($statsOfLogs as $resultId => $count) {
            if ($this->syncStats[$resultId] == $count) {
                $logAsCompleted[] = $resultId;
            }
        }

        $this->getResultSyncHistory()->logResultsLogsAsExported($logAsCompleted);

        return $this->report;
    }

    /**
     * @param array $logs
     * @return array
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function sendDeliveryLogs(array $logs)
    {
        $syncAcknowledgment = $this->getSyncClient()->sendDeliveryLogs($logs);

        if (empty($syncAcknowledgment)) {
            throw new \common_Exception('Error during result log synchronisation.
             No acknowledgment was provided by remote server.');
        }
        $syncSuccess = $syncFailed = [];

        foreach ($syncAcknowledgment as $id => $data) {
            if ((bool)$data['success']) {
                $syncSuccess[$id] = $data['noOfLogsSynced'];
                $this->syncStats[$id] += $data['noOfLogsSynced'];
            } else {
                $syncFailed[] = $id;
            }
        }

        if (!empty($syncSuccess)) {
            $this->report(count($syncSuccess) . ' result logs exports have been acknowledged.', LogLevel::INFO);
        }

        if (!empty($syncFailed)) {
            $this->report(count($syncFailed) . ' result logs exports have not been acknowledged.', LogLevel::ERROR);
        }

        return $syncSuccess;
    }

    /**
     * @param array $logs
     * @return array
     */
    public function importDeliveryLogs(array $logs)
    {
        $importAcknowledgment = [];
        $logsToBeInserted = [];
        foreach ($logs as $resultId => $resultLog) {
            try {
                $this->checkResultLogFormat($resultLog);
                $logsToBeInserted[] = $resultLog;

                $importAcknowledgment[$resultId] = [
                    'success' => 1,
                    'noOfLogsSynced' => count($logsToBeInserted),
                ];

            } catch (\Exception $exception) {
                $importAcknowledgment[$resultId] = [
                    'success' => 0,
                    'noOfLogsSynced' => 0,
                ];
            }

        }

        $this->getDeliveryLogService()->insertMultiple($logsToBeInserted);

        return $importAcknowledgment;
    }

    /**
     * @return array|ResultSyncHistoryService|object
     */
    protected function getResultSyncHistory()
    {
        return $this->getServiceLocator()->get(ResultSyncHistoryService::SERVICE_ID);
    }

    /**
     * @return array|DeliveryLog
     */
    protected function getDeliveryLogService()
    {
        return $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
    }

    /**
     * Report a message by log it and add it to $this->report
     *
     * @param $message
     * @param string $level
     * @throws \common_exception_Error
     */
    protected function report($message, $level = LogLevel::DEBUG)
    {
        switch ($level) {
            case LogLevel::INFO:
                $this->logInfo($message);
                $reportLevel = \common_report_Report::TYPE_SUCCESS;
                break;
            case LogLevel::ERROR:
                $this->logError($message);
                $reportLevel = \common_report_Report::TYPE_ERROR;
                break;
            case LogLevel::DEBUG:
            default:
                $this->logDebug($message);
                $reportLevel = \common_report_Report::TYPE_INFO;
                break;
        }
        $this->report->add(new \common_report_Report($reportLevel, $message));
    }


    /**
     * Get the number of delivery execution to send by request
     *
     * @return int
     */
    protected function getChunkSize()
    {
        return $this->hasOption(self::OPTION_CHUNK_SIZE) ? $this->getOption(self::OPTION_CHUNK_SIZE) : self::DEFAULT_CHUNK_SIZE;
    }

    /**
     * @return array|SynchronisationClient|object
     */
    protected function getSyncClient()
    {
        return $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
    }

    /**
     * Check if $result has the correct keys to be processed
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    protected function checkResultLogFormat($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Result Log is not correctly formatted, should be an array.');
        }

        $global = array('delivery_execution_id', 'event_id', 'data', 'created_at', 'created_by');
        if (!empty(array_diff_key(array_flip($global), $data))) {
            throw new \InvalidArgumentException('Result Log is not correctly formatted, should contains : ' . implode(', ', $global));
        }
    }
}