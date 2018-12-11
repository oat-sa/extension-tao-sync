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

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\Monitoring;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliveryRdf\helper\DetectTestAndItemIdentifiersHelper;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoResultServer\models\classes\ResultManagement;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\history\ResultSyncHistoryService;
use oat\taoSync\model\Mapper\OfflineResultToOnlineResultMapper;
use Psr\Log\LogLevel;
use qtism\common\enums\Cardinality;

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
    const OPTION_STATUS_EXECUTIONS_TO_SYNC = 'statusExecutionsToSync';

    const DEFAULT_CHUNK_SIZE = 10;

    /** @var \common_report_Report */
    protected $report;

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

        /** @var \core_kernel_classes_Resource $delivery */
        foreach ($this->getDeliveryAssemblyService()->getAllAssemblies() as $delivery) {
            $deliveryId = $delivery->getUri();
            /** @var DeliveryExecution $deliveryExecution */
            foreach ($this->getDeliveryExecutionByDelivery($delivery) as $deliveryExecution) {
                $deliveryExecutionId = $deliveryExecution->getIdentifier();
                $statesToSync        = $this->getExecutionsStatesAvailableForSync();
                $currentState        = $deliveryExecution->getState()->getUri();
                // Skip non white listed states of delivery executions.
                if (!in_array($currentState, $statesToSync)){
                    continue;
                }

                // Do not resend delivery execution already exported
                if ($this->getResultSyncHistory()->isAlreadyExported($deliveryExecutionId)) {
                    continue;
                }

                // Do no send delivery execution with no variables (deleted)
                $variables = $this->getDeliveryExecutionVariables($deliveryId, $deliveryExecutionId);
                if (empty($variables)) {
                    continue;
                }

                $this->report('Formatting delivery execution ' . $deliveryExecution->getIdentifier() . '...');
                $results[$deliveryExecutionId] = [
                    'deliveryId' => $deliveryId,
                    'deliveryExecutionId' => $deliveryExecutionId,
                    'details' => $this->getDeliveryExecutionDetails($deliveryExecutionId),
                    'variables' => $variables,
                ];

                $this->report(count($variables) . ' delivery execution variables found.');

                $counter++;

                if ($counter % $this->getChunkSize() === 0) {
                    $this->report($counter . ' delivery executions to send to remote server. Sending...', LogLevel::INFO);
                    $this->sendResults($results);
                    $results = [];
                }
            }
        }

        if ($counter === 0) {
            $this->report('No result to synchronize', LogLevel::INFO);
        }

        if (!empty($results)) {
            $this->sendResults($results);
        }

        return $this->report;

    }

    /**
     * Send results to remote server and process acknowledgment
     *
     * Delete results following configuration
     *
     * @param $results
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function sendResults($results)
    {
        $importAcknowledgment = $this->getSyncClient()->sendResults($results);
        if (empty($importAcknowledgment)) {
            throw new \common_Exception('Error during result synchronisation. No acknowledgment was provided by remote server.');
        }
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
        }
        if (!empty($syncFailed)) {
            $this->getResultSyncHistory()->logResultsAsExported($syncFailed, ResultSyncHistoryService::STATUS_FAILED);
            $this->report(count($syncFailed) . ' delivery execution exports have not been acknowledged.', LogLevel::ERROR);
        }

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
     * @param array $options
     * @return array
     */
    public function importDeliveryResults(array $results, array $options = [])
    {
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

                $this->saveBoxId(
                    $deliveryExecution,
                    $options[SyncServiceInterface::IMPORT_OPTION_BOX_ID] ?? null
                );
                $this->mapOfflineResultIdToOnlineResultId($resultId, $deliveryExecution->getIdentifier());
            } catch (\Exception $e) {
                $success = false;
            }

            if (isset($deliveryId)) {
                $importAcknowledgment[$resultId] = [
                    'success' => (int) $success,
                    'deliveryId' => $deliveryId,
                ];
            } else {
                $importAcknowledgment[$resultId] = [
                    'success' => (int) $success,
                ];
            }
        }

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
     * Get details of a delivery execution
     *
     * @param $deliveryExecutionId
     * @return array
     */
    protected function getDeliveryExecutionDetails($deliveryExecutionId)
    {
        /** @var DeliveryExecution $deliveryExecution */
        $deliveryExecution = $this->getDeliveryExecutionService()->getDeliveryExecution($deliveryExecutionId);
        try {
            return [
                'identifier' => $deliveryExecution->getIdentifier(),
                'label' => $deliveryExecution->getLabel(),
                'test-taker' => $deliveryExecution->getUserIdentifier(),
                'starttime' => $deliveryExecution->getStartTime(),
                'finishtime' => $deliveryExecution->getFinishTime(),
                'state' => $deliveryExecution->getState()->getUri(),
            ];
        } catch (\common_exception_NotFound $e) {
            return [];
        }
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
     * Get variables of a delivery execution
     *
     * @param $deliveryId
     * @param $deliveryExecutionId
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    protected function getDeliveryExecutionVariables($deliveryId, $deliveryExecutionId)
    {
        $variables = $this->getResultStorage($deliveryId)->getDeliveryVariables($deliveryExecutionId);
        $deliveryExecutionVariables = [];
        foreach ($variables as $variable) {
            $variable = (array) $variable[0];
            list($testIdentifier,$itemIdentifier) = $this->detectTestAndItemIdentifiers($deliveryId, $variable);
            $deliveryExecutionVariables[] = [
                'type' => $variable['class'],
                'callIdTest' => isset($variable['callIdTest'])? $variable['callIdTest'] : null,
                'callIdItem' => isset($variable['callIdItem']) ? $variable['callIdItem'] : null,
                'test' => $testIdentifier,
                'item' => $itemIdentifier,
                'data' => $variable['variable'],
            ];
        }

        return $deliveryExecutionVariables;
    }

    /**
     * @param $deliveryId
     * @param $variable
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    protected function detectTestAndItemIdentifiers($deliveryId, $variable)
    {
        $test = isset($variable['test']) ? $variable['test'] : null;
        $item = isset($variable['item']) ? $variable['item'] : null;
        return (new DetectTestAndItemIdentifiersHelper())->detect($deliveryId, $test, $item);
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
     * Get delivery executions by delivery
     *
     * @param \core_kernel_classes_Resource $delivery
     * @return array|DeliveryExecution[]
     */
    protected function getDeliveryExecutionByDelivery(\core_kernel_classes_Resource $delivery)
    {
        $serviceProxy = $this->getDeliveryExecutionService();
        if (!$serviceProxy instanceof Monitoring) {
            $resultStorage = $this->getResultStorage($delivery->getUri());
            $results = $resultStorage->getResultByDelivery([$delivery->getUri()]);
            $executions = [];
            foreach ($results as $result) {
                $executions[] = $serviceProxy->getDeliveryExecution($result['deliveryResultIdentifier']);
            }
        } else{
            $executions = $serviceProxy->getExecutionsByDelivery($delivery);
        }
        return $executions;
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
     * @return DeliveryAssemblyService
     */
    protected function getDeliveryAssemblyService()
    {
        return DeliveryAssemblyService::singleton();
    }

    /**
     * @return array
     */
    protected function getExecutionsStatesAvailableForSync()
    {
        $statuses = $this->getOption(static::OPTION_STATUS_EXECUTIONS_TO_SYNC);
        if ($statuses == null){
            return [DeliveryExecution::STATE_FINISHIED];
        }

        return $statuses;
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
     * Save in the result storage the box id of the Tao instance the results came from.
     * @param DeliveryExecution $deliveryExecution
     * @param string|null $boxId
     * @return boolean
     * @throws \common_Exception
     * @throws \common_exception_InvalidArgumentType
     * @throws \common_exception_NotFound
     * @throws \core_kernel_classes_EmptyProperty
     */
    public function saveBoxId(DeliveryExecution $deliveryExecution, $boxId)
    {
        if ($boxId === null) {
            return;
        }
        $delivery = $deliveryExecution->getDelivery();
        $testResource = DeliveryAssemblyService::singleton()->getOrigin($deliveryExecution->getDelivery());
        $resultVariable = new \taoResultServer_models_classes_TraceVariable();
        $resultVariable->setIdentifier('tao-boxId');
        $resultVariable->setBaseType('string');
        $resultVariable->setCardinality(Cardinality::getNameByConstant(Cardinality::SINGLE));
        $resultVariable->setTrace($boxId);

        try {
            $this->getResultStorage($delivery->getUri())->storeTestVariable(
                $deliveryExecution->getIdentifier(),
                $testResource->getUri(),
                $resultVariable,
                $deliveryExecution->getIdentifier()
            );
            return true;
        } catch (\common_Exception $e) {
            $this->logError(sprintf('Saving of box id has been failed. %s. Box id: %s; Result id: %s', $e->getMessage(), $deliveryExecution->getIdentifier(), $boxId));
            return false;
        }
    }
}