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

use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use \common_report_Report as Report;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\event\DeliveryExecutionIrregularityReport;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\history\ResultSyncHistoryService;
use oat\taoSync\model\Mapper\OfflineResultToOnlineResultMapper;
use Psr\Log\LogLevel;
use oat\taoSync\model\SyncServiceInterface;

class SyncDeliveryLogService extends ConfigurableService implements SyncDeliveryLogServiceInterface
{
    const OPTION_CHUNK_SIZE = 'chunkSize';
    const DEFAULT_CHUNK_SIZE = 200;
    const OPTION_SHOULD_DECODE_BEFORE_SYNC = 'shouldDecodeBeforeSync';
    const DELIVERY_LOG_SYNC_EVENT = 'SYNC_EVENT';

    /** @var Report */
    protected $report;

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
        $logsToSync = $deliveryLogService->getLogsToSynced($this->getOption(static::OPTION_SHOULD_DECODE_BEFORE_SYNC));

        $counter     = 0;
        $logs        = [];
        $sessionsSyncStatus = [];

        foreach ($logsToSync as $deliveryLog) {
            $resultId = $deliveryLog[DeliveryLog::DELIVERY_EXECUTION_ID];
            if (!isset($sessionsSyncStatus[$resultId])) {
                $sessionsSyncStatus[$resultId] = $this->getResultSyncHistory()->isSessionSynced($resultId);
            }
            $deliveryLog[EnhancedDeliveryLogService::LOG_IS_AFTER_SESSION_SYNCED] = $sessionsSyncStatus[$resultId];

            $logs[$resultId][]      = $deliveryLog;

            $counter++;
            if (($counter % $this->getChunkSize() === 0) || count($logsToSync) === $counter) {
                $this->report($counter . ' results logs to send to remote server. Sending...', LogLevel::INFO);
                $syncSuccess = $this->sendDeliveryLogs($logs);
                $logs = [];

                $this->markLogsAsSynced($syncSuccess);
            }
        }

        if (empty($logsToSync)) {
            $this->report('No result logs to synchronize', LogLevel::INFO);
        }

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
                $syncSuccess[$id] = $data['logsSynced'];
            } else {
                $syncFailed[] = $id;
            }

            if (!empty($syncSuccess) && isset($syncSuccess[$id])) {
                $this->report(count($syncSuccess[$id]). ' result logs exports have been acknowledged.', LogLevel::INFO);
            }

            if (!empty($syncFailed)) {
                $this->report(count($syncFailed) . ' result logs exports have not been acknowledged.', LogLevel::ERROR);
            }
        }

        return $syncSuccess;
    }

    /**
     * @param array $logs
     * @return array
     */
    public function importDeliveryLogs(array $logs, array $options = [])
    {
        $importAcknowledgment = [];
        foreach ($logs as $resultId => $resultLogs) {
            $logsToBeInserted = [];
            $logsSynced       = [];
            foreach ($resultLogs  as $resultLog) {
                try {
                    $this->checkResultLogFormat($resultLog);
                    $onlineResultId = $this->getOnlineIdOfOfflineResultId($resultLog['delivery_execution_id']);
                    if ($onlineResultId) {
                        $resultLog['delivery_execution_id'] = $onlineResultId;
                        $resultLog = $this->formatLog($resultLog);

                        $logsSynced[] = $resultLog['id'];
                        unset($resultLog['id']);
                        $logsToBeInserted[] = $resultLog;
                    }
                } catch (\Exception $exception) {
                    $this->logError($exception->getMessage());
                }
            }

            try {
                $this->getDeliveryLogService()->insertMultiple($logsToBeInserted);
                foreach ($logsToBeInserted as $deliveryLog) {
                    $this->postImportDeliverLogProcess($deliveryLog);
                }
                $this->saveBoxId($logsToBeInserted, $options[SyncServiceInterface::IMPORT_OPTION_BOX_ID] ?? null);
                $importAcknowledgment[$resultId] = [
                    'success' => 1,
                    'logsSynced' => $logsSynced
                ];

            } catch (\Exception $exception) {
                $importAcknowledgment[$resultId] = [
                    'success' => 0
                ];
            }
        }

        return $importAcknowledgment;
    }


    /**
     * @param string $offlineResultId
     * @return boolean
     * @throws \Exception
     */
    public function getOnlineIdOfOfflineResultId($offlineResultId)
    {
        /** @var OfflineResultToOnlineResultMapper $mapper */
        $mapper = $this->getServiceLocator()->get(OfflineResultToOnlineResultMapper::SERVICE_ID);

        return $mapper->getOnlineResultId($offlineResultId);
    }

    /**
     * @return array|ResultSyncHistoryService|object
     */
    protected function getResultSyncHistory()
    {
        return $this->getServiceLocator()->get(ResultSyncHistoryService::SERVICE_ID);
    }

    /**
     * @return array|EnhancedDeliveryLogService
     */
    protected function getDeliveryLogService()
    {
        return $this->getServiceLocator()->get(EnhancedDeliveryLogService::SERVICE_ID);
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

    /**
     * @param array $deliveryLog
     */
    protected function postImportDeliverLogProcess(array $deliveryLog)
    {
        if (isset($deliveryLog[DeliveryLog::EVENT_ID])
            && $deliveryLog[DeliveryLog::EVENT_ID] === 'TEST_IRREGULARITY'
            && isset($deliveryLog[EnhancedDeliveryLogService::LOG_IS_AFTER_SESSION_SYNCED])
            && $deliveryLog[EnhancedDeliveryLogService::LOG_IS_AFTER_SESSION_SYNCED] === true
        ) {
            $deliveryExecution = $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID)
                ->getDeliveryExecution($deliveryLog[DeliveryLog::DELIVERY_EXECUTION_ID]);

            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
            $eventManager->trigger(new DeliveryExecutionIrregularityReport($deliveryExecution));
        }
    }

    /**
     * @param array $deliveryLog
     * @return array
     */
    protected function formatLog(array $deliveryLog)
    {
        /** @var DeliveryLogFormatterService $deliveryLogFormatter */
        $deliveryLogFormatter = $this->getServiceLocator()->get(DeliveryLogFormatterService::SERVICE_ID);

        return $deliveryLogFormatter->format($deliveryLog);
    }

    /**
     * @param $syncSuccess
     * @throws \common_exception_Error
     */
    protected function markLogsAsSynced($syncSuccess)
    {
        $deliveryLogService = $this->getDeliveryLogService();

        foreach ($syncSuccess as $resultId => $logsSynced) {
            if (!empty($logsSynced)) {
                $deliveryLogService->markLogsAsSynced($logsSynced);
                $this->report(count($logsSynced) . ' delivery logs has been sync with success for result: '. $resultId);
            }
        }
    }

    /**
     * @param array $events
     * @param string $boxId
     * @return bool|void
     * @throws \common_exception_Error
     */
    public function saveBoxId($events, $boxId)
    {
        $user = \common_session_SessionManager::getSession()->getUser()->getIdentifier();
        if (empty($user) && PHP_SAPI == 'cli') {
            $user = 'cli';
        }
        $syncEvent = [
            DeliveryLog::DELIVERY_EXECUTION_ID => $events[0][DeliveryLog::DELIVERY_EXECUTION_ID],
            DeliveryLog::EVENT_ID => static::DELIVERY_LOG_SYNC_EVENT,
            DeliveryLog::DATA => json_encode([SyncServiceInterface::IMPORT_OPTION_BOX_ID => $boxId]),
            DeliveryLog::CREATED_AT => microtime(true),
            DeliveryLog::CREATED_BY => $user,
        ];
        $this->getDeliveryLogService()->insertMultiple([$syncEvent]);
    }

}
