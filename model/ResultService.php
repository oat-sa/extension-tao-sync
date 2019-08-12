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

namespace oat\taoSync\model;

use common_report_Report as Report;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoResultServer\models\classes\ResultManagement;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\event\SyncResponseEvent;
use oat\taoSync\model\history\ResultSyncHistoryService;
use oat\taoSync\model\Mapper\OfflineResultToOnlineResultMapper;
use oat\taoSync\model\Result\SyncResultDataFormatter;
use oat\taoSync\model\Result\SyncResultDataProvider;
use oat\taoSync\model\SyncLog\SyncLogDataHelper;
use Psr\Log\LogLevel;

/**
 * Class SyncService
 * @package oat\taoSync\model
 */
class ResultService extends ConfigurableService implements SyncResultServiceInterface
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/resultService';

    const OPTION_CHUNK_SIZE = 'chunkSize';
    const OPTION_DELETE_AFTER_SEND = 'deleteAfterSend';

    const DEFAULT_CHUNK_SIZE = 10;

    /** @var \common_report_Report */
    protected $report;
    /** @var array Synchronization parameters */
    protected $syncParams = [];

    /**
     * Scan delivery execution to format it
     *
     * Send results to remote server by configured chunk
     * Send only finished delivery execution
     * Do not resend already sent delivery execution
     * Log result has been sent into ResultHistoryService
     *
     * @param array $params
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     */
    public function synchronizeResults(array $params = [])
    {
        $this->report = \common_report_Report::createInfo('Starting delivery results synchronisation...');
        $results = [];
        $counter = 0;

        /** @var SyncResultDataProvider $dataProvider */
        $dataProvider = $this->getServiceLocator()->get(SyncResultDataProvider::SERVICE_ID);
        foreach ($dataProvider->getDeliveryExecutions($this->getChunkSize()) as $chunkOfDeliveryExecutions) {
            if (empty($chunkOfDeliveryExecutions)) {
                continue;
            }
            /** @var DeliveryExecution $deliveryExecution */
            foreach ($chunkOfDeliveryExecutions as $deliveryExecution) {
                $deliveryExecutionId = $deliveryExecution->getIdentifier();
                $this->report('Formatting delivery execution ' . $deliveryExecutionId . '...');

                $formattedDeliveryExecution = $this->getSyncResultsFormatterService()->format($deliveryExecution);
                if (empty($formattedDeliveryExecution)) {
                    continue;
                }
                $this->report(count($formattedDeliveryExecution['variables']) . ' delivery execution variables found.');

                $results[$deliveryExecutionId] = $formattedDeliveryExecution;
                $counter++;
            }

            $this->report($counter . ' delivery executions to send to remote server. Sending...', LogLevel::INFO);
            $this->sendResults($results, $params);
            $results = [];
        }

        if ($counter === 0) {
            $this->report('No result to synchronize', LogLevel::INFO);
        }

        return $this->report;
    }

    /**
     * Send results to remote server and process acknowledgment
     *
     * Delete results following configuration
     *
     * @param $results
     * @param array $params Synchronization parameters
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function sendResults($results, array $params = [])
    {
        if (empty($results)) {
            $this->report('No results to be synchronized.', LogLevel::INFO);
            return;
        }
        $importAcknowledgment = $this->getSyncClient()->sendResults($results, $params);
        if (empty($importAcknowledgment)) {
            throw new \common_Exception('Error during result synchronisation. No acknowledgment was provided by remote server.');
        }

        $logData = [self::SYNC_ENTITY => []];
        $syncSuccess = $syncFailed = [];
        foreach ($importAcknowledgment as $id => $data) {
            if ((bool) $data['success'] == true) {
                $syncSuccess[$id] = $data['deliveryId'];
            } else {
                $syncFailed[] = $id;
            }
        }

        if (!empty($syncSuccess)) {
            $this->getResultSyncHistory()->logResultsAsExported(array_keys($syncSuccess));
            $this->report(count($syncSuccess) . ' delivery execution exports have been acknowledged.', LogLevel::INFO);
            $logData[self::SYNC_ENTITY]['uploaded'] = count($syncSuccess);
        }
        if (!empty($syncFailed)) {
            $this->getResultSyncHistory()->logResultsAsExported($syncFailed, ResultSyncHistoryService::STATUS_FAILED);
            $this->report(count($syncFailed) . ' delivery execution exports have not been acknowledged.', LogLevel::ERROR);
            $logData[self::SYNC_ENTITY]['upload failed'] = count($syncFailed);
        }
        $this->report->setData(SyncLogDataHelper::mergeSyncData($this->report->getData(), $logData));

        if ($this->hasDeleteAfterSending()) {
            $this->deleteSynchronizedResult($syncSuccess);
        }
    }

    /**
     * Import delivery by scanning $results
     *
     * Spawn a delivery execution with delivery and test-taker
     * Create and inject variables
     *
     * @param array $results
     * @param array $params Synchronization parameters.
     * @return array
     */
    public function importDeliveryResults(array $results, array $params = [])
    {
        $this->initImport($params);
        $importAcknowledgment = [];

        foreach ($results as $resultId => $result) {
            $success = true;

            try {
                $this->checkResultFormat($result);

                $deliveryId = $result['deliveryId'];
                $details = $result['details'];
                $variables = $result['variables'];

                $delivery = $this->getResource($deliveryId);
                $testtaker = $this->getResource($details['test-taker']);

                $deliveryExecution = $this->spawnDeliveryExecution($resultId, $delivery, $testtaker);
                $deliveryExecution = $this->updateDeliveryExecution($details, $deliveryExecution);

                $this->getResultStorage($deliveryId)->storeRelatedTestTaker($deliveryExecution->getIdentifier(), $testtaker->getUri());
                $this->getResultStorage($deliveryId)->storeRelatedDelivery($deliveryExecution->getIdentifier(), $delivery->getUri());


                foreach ($variables as $variable) {
                    /** @var \taoResultServer_models_classes_Variable $resultVariable */
                    $resultVariable = $this->createVariable($variable['type'], $variable['data']);

                    $callIdItem = isset($variable['callIdItem']) ? $variable['callIdItem'] : null;
                    $test = $variable['test'];

                    if (is_null($callIdItem)) {
                        $callIdTest = $variable['callIdTest'];
                        $this->getResultStorage($deliveryId)->storeTestVariable(
                            $deliveryExecution->getIdentifier(),
                            $test,
                            $resultVariable,
                            $callIdTest
                        );

                    } else {
                        $item = $variable['item'];
                        $this->getResultStorage($deliveryId)->storeItemVariable(
                            $deliveryExecution->getIdentifier(),
                            $test,
                            $item,
                            $resultVariable,
                            $callIdItem
                        );
                    }

                }

                $this->mapOfflineResultIdToOnlineResultId($resultId, $deliveryExecution->getIdentifier());
            } catch (\Exception $e) {
                $success = false;
            }

            if (isset($deliveryId)) {
                $importAcknowledgment[$resultId] = [
                    'success' => (int) $success,
                    'deliveryId' => $deliveryId,
                ];
                $this->report->add(Report::createInfo("Delivery execution {$resultId} successfully imported."));
            } else {
                $importAcknowledgment[$resultId] = [
                    'success' => (int) $success,
                ];
                $this->report->add(Report::createFailure("Import failed for delivery execution {$resultId}."));
            }
        }

        $this->reportImportCompleted($importAcknowledgment);

        return $importAcknowledgment;
    }

    /**
     * @param string $offlineResultId
     * @param string $onlineResultId
     * @return boolean
     * @throws \common_Exception
     * @throws \Exception
     */
    public function mapOfflineResultIdToOnlineResultId($offlineResultId, $onlineResultId)
    {
        /** @var OfflineResultToOnlineResultMapper $mapper */
        $mapper = $this->getServiceLocator()->get(OfflineResultToOnlineResultMapper::SERVICE_ID);

        return $mapper->set($offlineResultId, $onlineResultId);
    }

    /**
     * Check if $result has the correct keys to be processed
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    protected function checkResultFormat($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Result is not correctly formatted, should be an array.');
        }

        $global = array('deliveryId', 'deliveryExecutionId', 'details', 'variables',);
        if (!empty(array_diff_key(array_flip($global), $data))) {
            throw new \InvalidArgumentException('Result is not correctly formatted, should contains : ' . implode(', ', $global));
        }

        $details = array('identifier', 'label', 'test-taker', 'starttime', 'finishtime', 'state',);
        if (!empty(array_diff_key(array_flip($details), $data['details']))) {
            throw new \InvalidArgumentException('Result details are not correctly formatted, should contains : ' . implode(', ', $details));
        }
    }

    /**
     * Create a variable from $type and cast $data into variable attributes
     *
     * @param $type
     * @param array $data
     * @return \taoResultServer_models_classes_Variable
     * @throws \common_exception_InvalidArgumentType
     */
    protected function createVariable($type, array $data)
    {
        switch ($type) {
            case \taoResultServer_models_classes_TraceVariable::class:
                $variable = new \taoResultServer_models_classes_TraceVariable();
                $variable->setIdentifier($data['identifier']);
                $variable->setBaseType($data['baseType']);
                $variable->setCardinality($data['cardinality']);
                $variable->setTrace($data['trace']);
                $variable->setEpoch($data['epoch']);
                break;
            case \taoResultServer_models_classes_ResponseVariable::class:
                $variable = new \taoResultServer_models_classes_ResponseVariable();
                $variable->setIdentifier($data['identifier']);
                $variable->setBaseType($data['baseType']);
                $variable->setCardinality($data['cardinality']);
                $variable->setCandidateResponse(base64_decode($data['candidateResponse']));
                $variable->setCorrectResponse($data['correctResponse']);
                $variable->setEpoch($data['epoch']);
                break;
            case \taoResultServer_models_classes_OutcomeVariable::class:
            default:
                $variable = new \taoResultServer_models_classes_OutcomeVariable();
                $variable->setIdentifier($data['identifier']);
                $variable->setBaseType($data['baseType']);
                $variable->setCardinality($data['cardinality']);
                $variable->setNormalMinimum($data['normalMinimum']);
                $variable->setNormalMaximum($data['normalMaximum']);
                $variable->setValue(base64_decode($data['value']));
                $variable->setEpoch($data['epoch']);
                break;
        }

        return $variable;
    }

    /**
     * Delete a delivery execution from array:
     * `array (
         'delivery1 => de1,
         'delivery1 => de2,
         'delivery2 => de3,
     * )`
     *
     * @param array $successfullyExportedResults
     * @throws \common_exception_Error
     */
    protected function deleteSynchronizedResult(array $successfullyExportedResults)
    {
        foreach ($successfullyExportedResults as $deliveryExecutionId => $deliveryId) {
            $this->report('Delete delivery id : ' . $deliveryExecutionId);
            $this->getResultStorage($deliveryId)->deleteResult($deliveryExecutionId);
        }

        $this->report(count($successfullyExportedResults) . ' deleted.', LogLevel::INFO);
    }

    /**
     * Init a delivery execution
     *
     * @param $delivery
     * @param $testtaker
     * @return DeliveryExecution
     * @throws \common_exception_Error
     * @throws \Exception
     */
    protected function spawnDeliveryExecution($resultId, $delivery, $testtaker)
    {
        $onlineId = $this->getOnlineIdOfOfflineResultId($resultId);

        if ($onlineId) {
            return $this->getDeliveryExecutionService()->getDeliveryExecution($onlineId);
        } else {
            return $this->getDeliveryExecutionService()->initDeliveryExecution($delivery, $testtaker);
        }
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
     * Get a boolean if the result has to be deleted after
     *
     * @return boolean
     */
    protected function hasDeleteAfterSending()
    {
        return $this->hasOption(self::OPTION_DELETE_AFTER_SEND) ? (bool) $this->getOption(self::OPTION_DELETE_AFTER_SEND) : false;
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
     * Fetch the delivery result server from delivery
     *
     * @param $deliveryId
     * @return ResultManagement | \taoResultServer_models_classes_WritableResultStorage
     */
    protected function getResultStorage($deliveryId)
    {
        return $this->getServiceLocator()->get(ResultServerService::SERVICE_ID)->getResultStorage($deliveryId);
    }

    /**
     * @return ResultSyncHistoryService
     */
    protected function getResultSyncHistory()
    {
        return $this->getServiceLocator()->get(ResultSyncHistoryService::SERVICE_ID);
    }

    /**
     * @return SyncResultDataFormatter
     */
    protected function getSyncResultsFormatterService()
    {
        return $this->getServiceLocator()->get(SyncResultDataFormatter::SERVICE_ID);
    }

    /**
     * @return SynchronisationClient
     */
    protected function getSyncClient()
    {
        return $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
    }

    /**
     * @return ServiceProxy
     */
    protected function getDeliveryExecutionService()
    {
        return $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID);
    }

    /**
     * @param array $details
     * @param DeliveryExecution $deliveryExecution
     * @return DeliveryExecution
     */
    protected function updateDeliveryExecution($details, $deliveryExecution)
    {
        if (isset($details['state'])) {
            $deliveryExecution->getImplementation()->setState($details['state']);
        }

        return $deliveryExecution;
    }

    /**
     * Initialize import.
     *
     * @param array $params
     */
    protected function initImport(array $params)
    {
        $this->report = Report::createInfo('Starting delivery executions import...');
        $this->syncParams = $params;
    }

    /**
     * Update report with import results.
     *
     * @param array $importAcknowledgments
     */
    protected function reportImportCompleted(array $importAcknowledgments)
    {
        $syncSuccess = $syncFailed = [];
        foreach ($importAcknowledgments as $acknowledgementId => $acknowledgementData) {
            if ((bool) $acknowledgementData['success'] == true) {
                $syncSuccess[$acknowledgementId] = $acknowledgementData['deliveryId'];
            } else {
                $syncFailed[] = $acknowledgementId;
            }
        }

        $syncReportData = [];
        if (!empty($syncSuccess)) {
            $syncReportData[self::SYNC_ENTITY]['imported'] = count($syncSuccess);
        }

        if (!empty($syncFailed)) {
            $syncReportData[self::SYNC_ENTITY]['import failed'] = count($syncFailed);
        }
        $this->report->setData($syncReportData);
        $this->getServiceLocator()->get(EventManager::SERVICE_ID)->trigger(
            new SyncResponseEvent($this->syncParams, $this->report)
        );
    }
}
