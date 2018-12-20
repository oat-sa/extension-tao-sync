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

namespace oat\taoSync\model\TestSession;

use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoQtiTest\models\ExtendedStateService;
use oat\taoQtiTest\models\runner\QtiRunnerService;
use oat\taoQtiTest\models\TestSessionService;
use oat\taoResultServer\models\Events\ResultCreated;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\DeliveryLog\EnhancedDeliveryLogService;
use oat\taoSync\model\history\ResultSyncHistoryService;
use common_report_Report as Report;
use oat\taoSync\model\Mapper\OfflineResultToOnlineResultMapper;
use oat\taoSync\model\SyncLog\SyncLogDataHelper;

/**
 * Class SyncTestSessionService
 * @package oat\taoSync\model\TestSession
 */
class SyncTestSessionService extends ConfigurableService implements SyncTestSessionServiceInterface
{

    /** @var Report */
    protected $report;

    /**
     * @param array $params
     * @return Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function synchronizeTestSession(array $params = [])
    {
        $this->report = Report::createInfo('Starting Test sessions synchronisation...');

        $sessionsToSync = $this->getResultSyncHistory()->getResultsWithTestSessionNotSynced();

        $sessions = [];
        $counter = 0;

        foreach ($sessionsToSync as $resultId) {
            if ($this->getDeliveryLogService()->hasAllDeliveryLogsSynced($resultId)) {
                $this->report->add(Report::createInfo('The Test session ('.$resultId.') to send to remote server. Sending...'));
                $sessions[] = $resultId;
                $counter++;
            }
        }

        if ($counter === 0) {
            $this->report->add(Report::createInfo('No Test sessions to synchronize'));
        }

        if (!empty($sessions)) {
            $this->sendTestSessions($sessions);
        }

        $this->getResultSyncHistory()->logTestSessionAsExported($sessions);

        return $this->report;
    }

    /**
     * @param array $session
     * @return array
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function sendTestSessions(array $session)
    {
        $syncAcknowledgment = $this->getSyncClient()->sendTestSessions($session);

        if (empty($syncAcknowledgment)) {
            throw new \common_Exception('Error during test sessions synchronisation.
             No acknowledgment was provided by remote server.');
        }
        $syncSuccess = $syncFailed = [];

        foreach ($syncAcknowledgment as $id => $data) {
            if ((bool)$data['success']) {
                $syncSuccess[$id] = 1;
            } else {
                $syncFailed[] = $id;
            }

            $logData = [self::SYNC_ENTITY => []];
            if (!empty($syncSuccess) && isset($syncSuccess[$id])) {
                $this->report->add(Report::createInfo($syncSuccess[$id] . ' sync sessions acknowledged.'));
                $logData[self::SYNC_ENTITY]['uploaded'] = 1;
            }

            if (!empty($syncFailed)) {
                $this->report->add(Report::createInfo(count($syncFailed) . ' sync sessions have not been acknowledged.'));
                $logData[self::SYNC_ENTITY]['upload failed'] = 1;
            }
            $this->report->setData(SyncLogDataHelper::mergeSyncData($this->report->getData(), $logData));
        }

        return $syncSuccess;
    }

    /**
     * @param array $session
     * @return array
     */
    public function importTestSessions(array $session)
    {
        $importAcknowledgment = [];
        foreach ($session as $resultId) {
            try {
                $onlineResultId = $this->getOnlineIdOfOfflineResultId($resultId);
                $deliveryExecution = $this->getServiceLocator()->get(ServiceProxy::SERVICE_ID)->getDeliveryExecution($onlineResultId);
                $this->afterImportSession($deliveryExecution);
                $importAcknowledgment[$resultId] = [
                    'success' => 1
                ];
            } catch (\Exception $exception) {
                $this->logError($exception->getMessage());
            }

        }
        return $importAcknowledgment;
    }


    /**
     * @param DeliveryExecution $deliveryExecution
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \qtism\runtime\storage\common\StorageException
     * @throws \qtism\runtime\tests\AssessmentTestSessionException
     */
    public function touchTestSession(DeliveryExecution $deliveryExecution)
    {
        /** @var TestSessionService $testSession */
        $testSession = $this->getServiceLocator()->get(TestSessionService::SERVICE_ID);

        if ($testSession->getTestSession($deliveryExecution) === null) {
            /** @var QtiRunnerService $runnerService */
            $runnerService =  $this->getServiceLocator()->get(QtiRunnerService::SERVICE_ID);

            $compiledDelivery = $deliveryExecution->getDelivery();
            $runtime = DeliveryAssemblyService::singleton()->getRuntime($compiledDelivery);
            $inputParameters = \tao_models_classes_service_ServiceCallHelper::getInputValues($runtime, []);

            $serviceContext = $runnerService->getServiceContext(
                $inputParameters['QtiTestDefinition'],
                $inputParameters['QtiTestCompilation'],
                $deliveryExecution->getIdentifier(),
                $deliveryExecution->getUserIdentifier()
            );
            $runnerService->init($serviceContext);
            $runnerService->persist($serviceContext);

            $session = $testSession->getTestSession($deliveryExecution);

            $testSession->persist($session);
            $this->getServiceManager()->get(ExtendedStateService::SERVICE_ID)->persist($session->getSessionId());
        }

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new ResultCreated($deliveryExecution));
    }

    /**
     * @return array|ResultSyncHistoryService|object
     */
    protected function getResultSyncHistory()
    {
        return $this->getServiceLocator()->get(ResultSyncHistoryService::SERVICE_ID);
    }

    /**
     * @return array|SynchronisationClient|object
     */
    protected function getSyncClient()
    {
        return $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
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
     * @param DeliveryExecution $deliveryExecution
     * @return bool
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     * @throws \common_exception_NotFound
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     * @throws \qtism\runtime\storage\common\StorageException
     * @throws \qtism\runtime\tests\AssessmentTestSessionException
     */
    protected function afterImportSession($deliveryExecution)
    {
        $this->touchTestSession($deliveryExecution);

        return true;
    }

    /**
     * @return array|EnhancedDeliveryLogService
     */
    protected function getDeliveryLogService()
    {
        return $this->getServiceLocator()->get(EnhancedDeliveryLogService::SERVICE_ID);
    }

}