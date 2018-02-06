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
use oat\taoDelivery\model\execution\Delete\DeliveryExecutionDeleteRequest;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\DeliveryExecutionInterface;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoOutcomeRds\model\RdsResultStorage;
use oat\taoResultServer\models\classes\ResultServerService;
use oat\taoSync\model\client\SynchronisationClient;
use Psr\Log\LogLevel;

/**
 * Class SyncService
 * @package oat\taoSync\model
 */
class ResultService extends ConfigurableService
{
    use OntologyAwareTrait;

    /** @var \common_report_Report */
    protected $report;

    public function synchronizeResults(array $params = [])
    {
        $this->report = \common_report_Report::createInfo('Starting delivery results synchronisation.');
        $results = [];
        $counter = 0;
        $deliveryExecutions = $this->getResultService()->getAllDeliveryIds();
        $deliveryExecutionCount = count($deliveryExecutions);

        foreach ($deliveryExecutions as $id) {
            $deliveryExecutionId = $id['deliveryResultIdentifier'];
            $deliveryId = $id['deliveryIdentifier'];
            $this->report('Formatting delivery execution ' . $deliveryExecutionId . '...');

            $results[$deliveryExecutionId] = [
                'deliveryId' => $deliveryId,
                'deliveryExecutionId' => $deliveryExecutionId,
                'details' => $this->getDeliveryExecutionDetails($deliveryExecutionId),
                'variables' => $this->getDeliveryExecutionVariables($deliveryExecutionId),
            ];

            $this->report(count($results[$deliveryExecutionId]['variables']) . ' delivery execution variables found.');
            $counter++;

            if ($counter % $this->getChunkSize() === 0 || $counter == $deliveryExecutionCount) {
                $this->report($counter . ' delivery executions to send to remote server. Sending...', LogLevel::INFO);
                $importAcknowledgment = $this->getSyncClient()->sendResults($results);
                $results = [];

                if ($this->hasDeleteAfterSending()) {
                    $this->report(count($importAcknowledgment) . ' delivery executions sent back from the server. Deleting locally...', LogLevel::INFO);
                    $this->deleteOnAcknowledgment($importAcknowledgment);
                }
            }
        }

        return $this->report;

    }

    /**
     * @todo manage start time
     *
     * @param array $results
     * @return array
     */
    public function importDeliveryResults(array $results)
    {

        foreach ($this->getResultService()->getAllDeliveryIds() as $id) {
            $deliveryExecutionId = $id['deliveryResultIdentifier'];
            $this->getResultService()->deleteResult($deliveryExecutionId);
        }

        $importAcknowledgment = [];

        foreach ($results as $resultId => $result) {
            $success = true;

            try {
                $delivery = $this->getResource($result['deliveryId']);
                $details = $result['details'];
                $testtaker = $this->getResource($details['test-taker']);

                $state = $details['state'];

//                $deliveryExecution = ServiceProxy::singleton()->initDeliveryExecution($this->getResource($delivery), $testtaker);
                $deliveryExecution = $this->spawnDeliveryExecution($delivery, $testtaker);
                $deliveryExecutionId = $deliveryExecution->getUserIdentifier();

                $this->getResultService()->storeRelatedTestTaker($deliveryExecutionId, $testtaker->getUri());
                $this->getResultService()->storeRelatedDelivery($deliveryExecutionId, $delivery->getUri());


                $this->getResultService()->storeRelatedDelivery($deliveryExecutionId, $delivery->getUri());

                $testtaker->setPropertyValue(
                    $this->getProperty('http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryExecutionDelivery'),
                    $deliveryExecutionId
                );

//                \common_Logger::i(print_r($deliveryExecution->getDelivery(), true));
//                \common_Logger::i(print_r($deliveryExecution->getIdentifier(), true));
//                \common_Logger::i(print_r($deliveryExecution->getUserIdentifier(), true));
//                \common_Logger::i(print_r($deliveryExecution->exists(), true));
//                $deliveryExecution->setState($state);
//
                $variables = $result['variables'];
                foreach ($variables as $variable) {
                    /** @var \taoResultServer_models_classes_Variable $resultVariable */
                    $resultVariable = $this->createVariable($variable['type'], $variable['data']);

                    $callIdItem = isset($variable['callIdItem']) ? $variable['callIdItem'] : null;
                    $test = $variable['test'];

                    if (is_null($callIdItem)) {
                        $callIdTest = $variable['callIdTest'];
                        $this->getResultService()->storeTestVariable(
                            $deliveryExecutionId,
                            $test,
                            $resultVariable,
                            $callIdTest
                        );

                    } else {
                        $item = $variable['item'];
                        $this->getResultService()->storeItemVariable(
                            $deliveryExecutionId,
                            $test,
                            $item,
                            $resultVariable,
                            $callIdItem
                        );
                    }

                }
            } catch (\Exception $e) {
                $success = false;
            }

            //@todo improve deletion. Now any above persist methods do not return anything
            $importAcknowledgment[$resultId] = (int) $success;
        }
        \common_Logger::i(print_r($importAcknowledgment, true));

        return $importAcknowledgment;
    }

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

    protected function getDeliveryExecutionVariables($deliveryExecutionId)
    {
        $variables = $this->getResultService()->getDeliveryVariables($deliveryExecutionId);
        $deliveryExecutionVariables = [];
        foreach ($variables as $variable) {
            $variable = (array) $variable[0];
            $deliveryExecutionVariables[] = [
                'type' => $variable['class'],
                'callIdTest' => isset($variable['callIdTest'])? $variable['callIdTest'] : null,
                'callIdItem' => isset($variable['callIdItem']) ? $variable['callIdItem'] : null,
                'test' => isset($variable['test']) ? $variable['test'] : null,
                'item' => isset($variable['item']) ? $variable['item'] : null,
                'data' => $variable['variable'],
            ];
        }
        return $deliveryExecutionVariables;
    }

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

    protected function deleteOnAcknowledgment(array $importAcknowledgment)
    {
        $countDelete = $countNotDelete= 0;
        foreach ($importAcknowledgment as $deliveryExecutionId => $ack) {
            if ((bool) $ack === true) {
                $this->report('Delete delivery id : ' . $deliveryExecutionId);
                $countDelete++;
                $this->getResultService()->deleteResult($deliveryExecutionId);
            } else {
                $countNotDelete++;
                $this->report('Delete delivery id : ' . $deliveryExecutionId);
            }
        }

        $this->report($countDelete . ' deleted.', LogLevel::INFO);
        $this->report($countNotDelete . ' has not been deleted.', LogLevel::INFO);
    }

    /**
     * @param $delivery
     * @param $testtaker
     * @return DeliveryExecutionInterface
     * @throws \common_exception_Error
     */
    protected function spawnDeliveryExecution($delivery, $testtaker)
    {
        return $this->getDeliveryExecutionService()->initDeliveryExecution($delivery, $testtaker);
    }

    /**
     * @todo retrieve from config
     * @todo add flag 'already-sent'
     *
     * @return bool
     */
    protected function hasDeleteAfterSending()
    {
        return false;
    }

    /**
     * @todo retrieve from config
     *
     * @return int
     */
    protected function getChunkSize()
    {
        return 100;
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
//                $this->logInfo($message);
                $reportLevel = \common_report_Report::TYPE_SUCCESS;
                break;
            case LogLevel::ERROR:
//                $this->logError($message);
                $reportLevel = \common_report_Report::TYPE_ERROR;
                break;
            case LogLevel::DEBUG:
            default:
//                $this->logDebug($message);
                $reportLevel = \common_report_Report::TYPE_INFO;
                break;
        }
        $this->report->add(new \common_report_Report($reportLevel, $message));
    }

    /**
     * @return RdsResultStorage
     */
    protected function getResultService($deliveryId)
    {
        return $this->getServiceLocator()->get(ResultServerService::SERVICE_ID)->getResultStorage($deliveryId);
    }

    /**
     * @return \taoResultServer_models_classes_WritableResultStorage
     */
    protected function getWritableStorage()
    {
        return $this->getServiceLocator()->get(ResultServerService::SERVICE_ID)->getResultStorage();
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

}