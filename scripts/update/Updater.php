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

namespace oat\taoSync\scripts\update;

use Doctrine\DBAL\Types\Type;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\TaoOntology;
use oat\tao\model\user\import\UserCsvImporterFactory;
use oat\tao\model\user\TaoRoles;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\controller\HandShake;
use oat\taoSync\controller\RestSupportedVm;
use oat\taoSync\model\DeliveryLog\DeliveryLogFormatterService;
use oat\taoSync\model\DeliveryLog\EnhancedDeliveryLogService;
use oat\taoSync\model\DeliveryLog\SyncDeliveryLogService;
use oat\taoSync\model\Entity;
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\event\SyncRequestEvent;
use oat\taoSync\model\event\SyncResponseEvent;
use oat\taoSync\model\Execution\DeliveryExecutionStatusManager;
use oat\taoSync\model\history\byOrganisationId\DataSyncHistoryByOrgIdService;
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\history\ResultSyncHistoryService;
use oat\taoSync\model\import\SyncUserCsvImporter;
use oat\taoSync\model\listener\CentralSyncLogListener;
use oat\taoSync\model\Mapper\OfflineResultToOnlineResultMapper;
use oat\taoSync\model\OfflineMachineChecksService;
use oat\taoSync\model\Parser\DeliveryExecutionContextParser;
use oat\taoSync\model\ResultService;
use oat\taoSync\model\server\HandShakeServerService;
use oat\taoSync\model\testCenter\SyncManagerTreeService;
use oat\taoSync\model\VirtualMachine\SupportedVmService;
use oat\taoSync\model\SynchronizationHistory\HistoryPayloadFormatter;
use oat\taoSync\model\SynchronizationHistory\HistoryPayloadFormatterInterface;
use oat\taoSync\model\SynchronizationHistory\SynchronizationHistoryService;
use oat\taoSync\model\SynchronizationHistory\SynchronizationHistoryServiceInterface;
use oat\taoSync\model\SynchronizeAllTaskBuilderService;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizer;
use oat\taoSync\model\synchronizer\user\proctor\ProctorSynchronizer;
use oat\taoSync\model\SyncLog\Storage\RdsSyncLogStorage;
use oat\taoSync\model\SyncLog\Storage\SyncLogStorageInterface;
use oat\taoSync\model\SyncLog\SyncLogClientStateParser;
use oat\taoSync\model\SyncLog\SyncLogDataParser;
use oat\taoSync\model\SyncLog\SyncLogService;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;
use oat\taoSync\model\SyncService;
use oat\taoSync\model\testCenter\TestCenterService;
use oat\taoSync\model\TestSession\SyncTestSessionService;
use oat\taoSync\model\User\HandShakeClientService;
use oat\taoSync\model\Validator\SyncParamsValidator;
use oat\taoSync\model\VirtualMachine\VmIdentifierService;
use oat\taoSync\model\VirtualMachine\VmVersionChecker;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;
use oat\taoSync\model\ui\FormFieldsService;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeDeliveryLog;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeResult;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeTestSession;
use oat\taoSync\scripts\tool\RenameColumnOrgId;
use oat\taoSync\scripts\install\RegisterRdsSyncLogStorage;
use oat\taoTestCenter\model\ProctorManagementService;
use oat\taoSync\model\event\RequestStatsEvent;
use oat\taoSync\model\listener\ClientSyncLogListener;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\client\ConnectionStatsHandler;

/**
 * Class Updater
 *
 * @author Moyon Camille <camille@taotesting.com>
 * @author Dieter Raber <dieter@taotesting.com>
 */
class Updater extends \common_ext_ExtensionUpdater
{
    /**
     * @param $initialVersion
     * @return string|void
     * @throws \Exception
     */
    public function update($initialVersion)
    {
        $this->skip('0.0.1','0.1.0');

        if ($this->isVersion('0.1.0')) {
            $this->getServiceManager()->register(FormFieldsService::SERVICE_ID, new FormFieldsService());

            // include the Sync master role
            OntologyUpdater::syncModels();
            $this->setVersion('0.2.0');
        }

        if ($this->isVersion('0.2.0')) {
            OntologyUpdater::syncModels();
            $service = $this->getServiceManager()->get(PublishingService::SERVICE_ID);
            $actions = $service->getOption(PublishingService::OPTIONS_ACTIONS);
            $updatePublishingService = false;
            if (!in_array(SynchronizeData::class, $actions)) {
                $actions[] = SynchronizeData::class;
                $updatePublishingService = true;
            }
            if (in_array('oat\\taoSync\\scripts\\tool\\SynchronizeData', $actions)) {
                unset($actions[array_search('oat\\taoSync\\scripts\\tool\\SynchronizeData', $actions)]);
                $updatePublishingService = true;
            }
            if ($updatePublishingService) {
                $service->setOption(PublishingService::OPTIONS_ACTIONS, $actions);
                $this->getServiceManager()->register(PublishingService::SERVICE_ID, $service);
            }

            $this->setVersion('0.3.0');
        }

        $this->skip('0.3.0', '0.9.0');
        if ($this->isVersion('0.9.0')){
            $handShakeService = new HandShakeClientService([
                HandShakeClientService::OPTION_ROOT_URL => 'http://tao.dev/',
                HandShakeClientService::OPTION_REMOTE_AUTH_URL => 'http://tao.dev/taoSync/HandShake'
            ]);

            $this->getServiceManager()->register(HandShakeClientService::SERVICE_ID, $handShakeService);

            $handShakeServerService = new HandShakeServerService([]);

            $this->getServiceManager()->register(HandShakeServerService::SERVICE_ID, $handShakeServerService);

            $this->setVersion('0.10.0');
        }

        $this->skip('0.10.0','0.10.3');

        if ($this->isVersion('0.10.3')) {
            /** @var UserCsvImporterFactory $importerFactory */
            $importerFactory = $this->getServiceManager()->get(UserCsvImporterFactory::SERVICE_ID);
            $typeOptions = $importerFactory->getOption(UserCsvImporterFactory::OPTION_MAPPERS);
            $typeOptions[SyncUserCsvImporter::USER_IMPORTER_TYPE] = array(
                UserCsvImporterFactory::OPTION_MAPPERS_IMPORTER => new SyncUserCsvImporter()
            );
            $importerFactory->setOption(UserCsvImporterFactory::OPTION_MAPPERS, $typeOptions);
            $this->getServiceManager()->register(UserCsvImporterFactory::SERVICE_ID, $importerFactory);

            $this->setVersion('0.11.0');
        }

        $this->skip('0.11.0','0.11.1');

        if ($this->isVersion('0.11.1')) {
            /** @var FileSystemService $fileSystemService */
            $fileSystemService = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
            /** @var FileSystem $fileSystem */
            $fileSystem = $fileSystemService->getFileSystem('synchronisation');

            $fileSystem->put('config/handshakedone', 0);

            $this->setVersion('0.11.2');
        }

        if ($this->isVersion('0.11.2')) {
            AclProxy::applyRule(new AccessRule(
                'grant', 'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole', HandShake::class
            ));
            $this->setVersion('0.12.0');
        }

        $this->skip('0.12.0','0.12.1');

        if ($this->isVersion('0.12.1')){

            $service = new SynchronizeAllTaskBuilderService([
                SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC => [
                    SynchronizeData::class,
                    SynchronizeResult::class,
                ]
            ]);

            $this->getServiceManager()->register(SynchronizeAllTaskBuilderService::SERVICE_ID, $service);

            /** @var ResultService $syncResultService */
            $syncResultService = $this->getServiceManager()->get(ResultService::SERVICE_ID);
            $syncResultService->setOption(ResultService::OPTION_STATUS_EXECUTIONS_TO_SYNC, [
                DeliveryExecution::STATE_FINISHIED
            ]);

            $this->getServiceManager()->register(ResultService::SERVICE_ID, $syncResultService);
            $this->setVersion('0.12.2');
        }

        $this->skip('0.12.2','0.14.1');

        if ($this->isVersion('0.14.1')){

            $service = new SynchronizeAllTaskBuilderService([
                SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC => [
                    SynchronizeData::class,
                    SynchronizeResult::class,
                ]
            ]);

            $this->getServiceManager()->register(SynchronizeAllTaskBuilderService::SERVICE_ID, $service);

            /** @var ResultService $syncResultService */
            $syncResultService = $this->getServiceManager()->get(ResultService::SERVICE_ID);
            $syncResultService->setOption(ResultService::OPTION_STATUS_EXECUTIONS_TO_SYNC, [
                DeliveryExecution::STATE_FINISHIED
            ]);

            $this->getServiceManager()->register(ResultService::SERVICE_ID, $syncResultService);
            $this->setVersion('0.14.2');
        }

        $this->skip('0.14.2','1.0.0');

        if ($this->isVersion('1.0.0')) {
            $service = $this->getServiceManager()->get(SyncService::SERVICE_ID);
            $options = $service->getOptions();
            if (
                isset($options[SyncService::OPTION_SYNCHRONIZERS])
                && isset($options[SyncService::OPTION_SYNCHRONIZERS][DeliverySynchronizer::SYNC_DELIVERY])
            ) {
                /** @var ConfigurableService $deliverySynchronizer */
                $deliverySynchronizer = $options[SyncService::OPTION_SYNCHRONIZERS][DeliverySynchronizer::SYNC_DELIVERY];
                $deliverySynchronizerOptions = $deliverySynchronizer->getOptions();
                if (isset($deliverySynchronizerOptions[AbstractResourceSynchronizer::OPTIONS_FIELDS])) {
                    unset($deliverySynchronizerOptions[AbstractResourceSynchronizer::OPTIONS_FIELDS]);
                }
                $excludedFields = [];
                if (isset($deliverySynchronizerOptions[AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS])) {
                    $excludedFields =
                        $deliverySynchronizerOptions[AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS]
                        + array(
                            TaoOntology::PROPERTY_UPDATED_AT,
                            Entity::CREATED_AT,
                            DeliveryAssemblyService::PROPERTY_ORIGIN,
                            DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY,
                            DeliveryAssemblyService::PROPERTY_DELIVERY_TIME,
                            DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME,
                            ContainerRuntime::PROPERTY_CONTAINER,
                        );
                }
                $deliverySynchronizerOptions[AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS] = $excludedFields;
                $deliverySynchronizer->setOptions($deliverySynchronizerOptions);
                $options[SyncService::OPTION_SYNCHRONIZERS][DeliverySynchronizer::SYNC_DELIVERY] = $deliverySynchronizer;
                $service->setOptions($options);
                $this->getServiceManager()->register(SyncService::SERVICE_ID, $service);
            }
            $this->setVersion('1.0.1');
        }

        $this->skip('1.0.1', '1.0.2');

        if ($this->isVersion('1.0.2')) {

            /** @var SyncService $service */
            $service = $this->getServiceManager()->get(SyncService::SERVICE_ID);
            $options = $service->getOptions();

            if (
                isset($options[SyncService::OPTION_SYNCHRONIZERS])
                && isset($options[SyncService::OPTION_SYNCHRONIZERS][ProctorSynchronizer::SYNC_PROCTOR])
            ) {
                /** @var ConfigurableService $proctorSynchronizer */
                $proctorSynchronizer = $options[SyncService::OPTION_SYNCHRONIZERS][ProctorSynchronizer::SYNC_PROCTOR];
                $proctorSynchronizerOptions = $proctorSynchronizer->getOptions();

                if (isset($proctorSynchronizerOptions[AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS])) {
                    $excludedFields = $proctorSynchronizerOptions[AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS];

                    if (array_search(ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI, $excludedFields) === false) {
                        $excludedFields[] = ProctorManagementService::PROPERTY_AUTHORIZED_PROCTOR_URI;
                        $proctorSynchronizerOptions[AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS] = $excludedFields;

                        $proctorSynchronizer->setOptions($proctorSynchronizerOptions);
                        $options[SyncService::OPTION_SYNCHRONIZERS][ProctorSynchronizer::SYNC_PROCTOR] = $proctorSynchronizer;
                        $service->setOptions($options);
                        $this->getServiceManager()->register(SyncService::SERVICE_ID, $service);
                    }
                }

            }

            $this->setVersion('1.1.0');
        }

        if ($this->isVersion('1.1.0')) {

            /** @var \common_persistence_SqlPersistence $persistence */
            $persistence = \common_persistence_Manager::getPersistence('default');
            $schemaManager = $persistence->getSchemaManager();
            $fromSchema = $schemaManager->createSchema();
            $toSchema = clone $fromSchema;

            $table = $toSchema->getTable(ResultSyncHistoryService::SYNC_RESULT_TABLE);
            if (!$table->hasColumn(ResultSyncHistoryService::SYNC_LOG_SYNCED)) {
                $table->addColumn(ResultSyncHistoryService::SYNC_LOG_SYNCED, 'integer', ['notnull' => true, 'length' => 1, 'default' => 0]);
                $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $toSchema);
                foreach ($queries as $query) {
                    $persistence->exec($query);
                }
            }

            $syncDeliveryLog = new SyncDeliveryLogService();
            $this->getServiceManager()->register(SyncDeliveryLogService::SERVICE_ID, $syncDeliveryLog);

            /** @var SynchronizeAllTaskBuilderService  $syncAll */
            $syncAll = $this->getServiceManager()->get(SynchronizeAllTaskBuilderService::SERVICE_ID);
            $options = $syncAll->getOption(SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC);
            $options[] = SynchronizeDeliveryLog::class;

            $syncAll->setOption(SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC, $options);
            $this->getServiceManager()->register(SynchronizeAllTaskBuilderService::SERVICE_ID, $syncAll);

            $persistenceId = 'mapOfflineToOnlineResultIds';

            try {
                \common_persistence_Manager::getPersistence($persistenceId);
            } catch (\common_Exception $e) {
                \common_persistence_Manager::addPersistence($persistenceId,  array(
                    'driver' => 'SqlKvWrapper',
                    'sqlPersistence' => 'default',
                ));
            }

            $mapper = new OfflineResultToOnlineResultMapper([
                OfflineResultToOnlineResultMapper::OPTION_PERSISTENCE => 'mapOfflineToOnlineResultIds'
            ]);

            $this->getServiceManager()->register(OfflineResultToOnlineResultMapper::SERVICE_ID, $mapper);

            $this->setVersion('1.2.0');
        }

        $this->skip('1.2.0', '1.2.1');

        if ($this->isVersion('1.2.1') || $this->isVersion('1.2.1.1')) {

            /** @var \common_persistence_SqlPersistence $persistence */
            $persistence = \common_persistence_Manager::getPersistence('default');
            $schemaManager = $persistence->getSchemaManager();
            $fromSchema = $schemaManager->createSchema();
            $toSchema = clone $fromSchema;

            $table = $toSchema->getTable(ResultSyncHistoryService::SYNC_RESULT_TABLE);
            if (!$table->hasColumn(ResultSyncHistoryService::SYNC_SESSION_SYNCED)) {
                $table->addColumn(ResultSyncHistoryService::SYNC_SESSION_SYNCED, 'integer', ['notnull' => true, 'length' => 1, 'default' => 0]);
                $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $toSchema);
                foreach ($queries as $query) {
                    $persistence->exec($query);
                }
            }

            $syncTestSession = new SyncTestSessionService();
            $this->getServiceManager()->register(SyncTestSessionService::SERVICE_ID, $syncTestSession);

            /** @var SynchronizeAllTaskBuilderService  $syncAll */
            $syncAll = $this->getServiceManager()->get(SynchronizeAllTaskBuilderService::SERVICE_ID);
            $options = $syncAll->getOption(SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC);
            $options[] = SynchronizeTestSession::class;

            $syncAll->setOption(SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC, $options);
            $this->getServiceManager()->register(SynchronizeAllTaskBuilderService::SERVICE_ID, $syncAll);

            $this->setVersion('1.3.0');
        }

        $this->skip('1.3.0', '1.3.1');

        if ($this->isVersion('1.3.1')) {
            $this->addColumnToDeliveryLog();
            $this->dropColumnIsSynced();

            $deliveryLog = new EnhancedDeliveryLogService(['persistence' => 'default']);
            $this->getServiceManager()->register(EnhancedDeliveryLogService::SERVICE_ID, $deliveryLog);

            /** @var RdsDeliveryLogService $deliveryLog */
            $deliveryLog = $this->getServiceManager()->get(RdsDeliveryLogService::SERVICE_ID);

            $deliveryLog->setOption(RdsDeliveryLogService::OPTION_FIELDS, [
                RdsDeliveryLogService::EVENT_ID,
                RdsDeliveryLogService::CREATED_BY,
                RdsDeliveryLogService::DELIVERY_EXECUTION_ID,
                EnhancedDeliveryLogService::COLUMN_IS_SYNCED
            ]);

            $this->getServiceManager()->register(RdsDeliveryLogService::SERVICE_ID, $deliveryLog);

            $deliveryLogFormatter = new DeliveryLogFormatterService([]);
            $this->getServiceManager()->register(DeliveryLogFormatterService::SERVICE_ID, $deliveryLogFormatter);

            /** @var SyncDeliveryLogService $syncDeliveryLog */
            $syncDeliveryLog = $this->getServiceManager()->get(SyncDeliveryLogService::SERVICE_ID);
            $syncDeliveryLog->setOption(SyncDeliveryLogService::OPTION_SHOULD_DECODE_BEFORE_SYNC, true);
            $this->getServiceManager()->register(SyncDeliveryLogService::SERVICE_ID, $syncDeliveryLog);

            $this->setVersion('1.4.0');
        }

        $this->skip('1.4.0', '1.6.5');

        if ($this->isVersion('1.6.5')) {

            /** @var DataSyncHistoryService $service */
            $service = $this->getServiceManager()->get(DataSyncHistoryService::SERVICE_ID);

            $persistence = $service->getPersistence();

            /** @var \common_persistence_sql_SchemaManager $schemaManager */
            $schemaManager = $persistence->getDriver()->getSchemaManager();
            $schema = $schemaManager->createSchema();
            $syncTable = $schema->getTable(DataSyncHistoryByOrgIdService::SYNC_TABLE);

            if ($syncTable->hasColumn(RenameColumnOrgId::COLUMN_OLD)) {
                $this->addReport(\common_report_Report::createFailure(RenameColumnOrgId::class . ' must be executed first'));
            } else {
                /** @var FormFieldsService $formFieldsService */
                $formFieldsService = $this->getServiceManager()->get(FormFieldsService::SERVICE_ID);
                $fields = (array)$formFieldsService->getOption(FormFieldsService::OPTION_INPUT);

                $orgIdField = [
                    TestCenterByOrganisationId::OPTION_ORGANISATION_ID => [
                        'element' => 'input',
                        'attributes' => [
                            'required' => true,
                            'minlength' => 2
                        ],
                        'label' => __('Organisation identifier')
                    ]
                ];

                unset($fields[RenameColumnOrgId::COLUMN_OLD]);
                $formFieldsService->setOption(FormFieldsService::OPTION_INPUT, array_merge($fields, $orgIdField));
                $this->getServiceManager()->register(FormFieldsService::SERVICE_ID, $formFieldsService);

                $this->logInfo('Configured new form fields for synchronization form.');

                $this->setVersion('1.6.6');
            }
        }

        $this->skip('1.6.6', '2.1.0');

        if ($this->isVersion('2.1.0')) {
            /** @var SyncService $service */
            $service = $this->getServiceManager()->get(SyncService::SERVICE_ID);
            if (!$service->hasOption(SyncService::OPTION_CHECK_ACTIVE_SESSIONS)) {
                $service->setOption(SyncService::OPTION_CHECK_ACTIVE_SESSIONS, false);
            }
            $this->getServiceManager()->register(SyncService::SERVICE_ID, $service);

            $this->setVersion('2.2.0');
        }

        $this->skip('2.2.0', '3.1.0');

        if ($this->isVersion('3.1.0')) {
            $storage = new RdsSyncLogStorage([
                RdsSyncLogStorage::OPTION_PERSISTENCE_ID => 'default'
            ]);
            $this->getServiceManager()->register(RdsSyncLogStorage::SERVICE_ID, $storage);
            $registerRdsSyncLogStorage = new RegisterRdsSyncLogStorage();
            $registerRdsSyncLogStorage->setServiceLocator($this->getServiceManager());
            $registerRdsSyncLogStorage->createTable($storage->getPersistence());
            $this->setVersion('3.2.0');
        }

        if ($this->isVersion('3.2.0')) {
            $syncLogService = new SyncLogService([
                SyncLogService::OPTION_STORAGE => RdsSyncLogStorage::SERVICE_ID
            ]);
            $syncLogService->setServiceLocator($this->getServiceManager());
            $this->getServiceManager()->register(SyncLogServiceInterface::SERVICE_ID, $syncLogService);

            $this->setVersion('3.3.0');
        }

        if ($this->isVersion('3.3.0')) {
            $syncLogDataParser = new SyncLogDataParser([]);
            $syncLogDataParser->setServiceLocator($this->getServiceManager());
            $this->getServiceManager()->register(SyncLogDataParser::SERVICE_ID, $syncLogDataParser);

            $this->setVersion('3.4.0');
        }

        $this->skip('3.4.0', '4.2.0');

        if ($this->isVersion('4.2.0')) {
            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            // Detach event listeners if registered
            $eventManager->detach(SyncRequestEvent::class, [CentralSyncLogListener::SERVICE_ID, 'logSyncRequest']);
            $eventManager->detach(SyncResponseEvent::class, [CentralSyncLogListener::SERVICE_ID, 'logSyncResponse']);
            $eventManager->detach(SyncFinishedEvent::class, [CentralSyncLogListener::SERVICE_ID, 'logSyncFinished']);

            // Attach event listeners.
            $eventManager->attach(SyncRequestEvent::class, [CentralSyncLogListener::SERVICE_ID, 'logSyncRequest']);
            $eventManager->attach(SyncResponseEvent::class, [CentralSyncLogListener::SERVICE_ID, 'logSyncResponse']);
            $eventManager->attach(SyncFinishedEvent::class, [CentralSyncLogListener::SERVICE_ID, 'logSyncFinished']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            if (!$this->getServiceManager()->has(CentralSyncLogListener::SERVICE_ID)) {
                $syncLogListener = new CentralSyncLogListener([]);
                $this->getServiceManager()->register(CentralSyncLogListener::SERVICE_ID, $syncLogListener);
            }

            // Register synchronization history services for central server.
            if (!$this->getServiceManager()->has(HistoryPayloadFormatterInterface::SERVICE_ID)) {
                $options = [
                    HistoryPayloadFormatter::OPTION_DATA_MODEL => [
                        'status' => [
                            'id' => 'status',
                            'label' => __('Result'),
                            'sortable' => true,
                        ],
                        'created_at' => [
                            'id' => 'created_at',
                            'label' => __('Started at'),
                            'sortable' => true,
                        ],
                        'finished_at' => [
                            'id' => 'finished_at',
                            'label' => __('Finished at'),
                            'sortable' => true,
                        ],
                        'data' => [
                            'id' => 'data',
                            'label' => __('Data'),
                            'sortable' => false,
                        ],
                        'organisation' => [
                            'id' => 'organisation',
                            'label' => __('Organisation ID'),
                            'sortable' => false,
                        ],
                        'box_id' => [
                            'id' => 'box_id',
                            'label' => __('Box ID'),
                            'sortable' => false
                        ],
                    ]
                ];
                $payloadFormatter = new HistoryPayloadFormatter($options);
                $this->getServiceManager()->register(HistoryPayloadFormatterInterface::SERVICE_ID, $payloadFormatter);
            }

            if (!$this->getServiceManager()->has(SynchronizationHistoryServiceInterface::SERVICE_ID)) {
                $synchronisationHistoryService = new SynchronizationHistoryService([]);
                $this->getServiceManager()->register(SynchronizationHistoryServiceInterface::SERVICE_ID, $synchronisationHistoryService);
            }

            $this->setVersion('4.3.0');
        }

        $this->skip('4.3.0', '4.4.0');

        if ($this->isVersion('4.4.0')) {
            $this->getServiceManager()->register(TestCenterService::SERVICE_ID, new TestCenterService());
            $this->setVersion('4.5.0');
        }

        $this->skip('4.5.0', '4.6.0');

        if ($this->isVersion('4.6.0')) {
            $this->getServiceManager()->register(OfflineMachineChecksService::SERVICE_ID, new OfflineMachineChecksService([OfflineMachineChecksService::OPTION_CHECKS => []]));
            $this->setVersion('4.7.0');
        }

        $this->skip('4.7.0', '4.7.1');


        if ($this->isVersion('4.7.1')) {
            $executionStatusManager = new DeliveryExecutionStatusManager();
            $this->getServiceManager()->register(DeliveryExecutionStatusManager::SERVICE_ID, $executionStatusManager);

            $executionContextParser = new DeliveryExecutionContextParser();
            $this->getServiceManager()->register(DeliveryExecutionContextParser::SERVICE_ID, $executionContextParser);

            $this->setVersion('5.0.0');
        }

        $this->skip('5.0.0', '5.0.2');

        if ($this->isVersion('5.0.2')) {
            $syncLogClientStateParser = new SyncLogClientStateParser([]);
            $syncLogClientStateParser->setServiceLocator($this->getServiceManager());
            $this->getServiceManager()->register(SyncLogClientStateParser::SERVICE_ID, $syncLogClientStateParser);

            if ($this->getServiceManager()->has(RdsSyncLogStorage::SERVICE_ID)) {
                /** @var RdsSyncLogStorage $storage */
                $storage = $this->getServiceManager()->get(RdsSyncLogStorage::SERVICE_ID);
                $persistence = $storage->getPersistence();
                $schemaManager = $persistence->getSchemaManager();
                $fromSchema = $schemaManager->createSchema();
                $toSchema = clone $fromSchema;
                $table = $toSchema->getTable(RdsSyncLogStorage::TABLE_NAME);
                if (!$table->hasColumn(SyncLogStorageInterface::COLUMN_CLIENT_STATE)) {
                    $table->addColumn(SyncLogStorageInterface::COLUMN_CLIENT_STATE, 'text', ['notnull' => false, 'default' => null]);
                    $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $toSchema);
                    foreach ($queries as $query) {
                        $persistence->exec($query);
                    }
                }
            }

            $this->setVersion('5.1.0');
        }

        if ($this->isVersion('5.1.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('5.2.0');
        }

        if ($this->isVersion('5.2.0')) {
            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

            // Attach event listener for SyncFailedEvent.
            $eventManager->attach(SyncFailedEvent::class, [CentralSyncLogListener::SERVICE_ID, 'logSyncFailed']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('5.3.0');
        }

        $this->skip('5.3.0', '5.4.0');

        if ($this->isVersion('5.4.0')) {
            OntologyUpdater::syncModels();
            AclProxy::applyRule(
                new AccessRule(
                    AccessRule::GRANT,
                    TaoRoles::ANONYMOUS,
                    RestSupportedVm::class
                )
            );

            $serviceManager = $this->getServiceManager();
            $supportedVmService = new SupportedVmService();
            $serviceManager->register(SupportedVmService::SERVICE_ID, $supportedVmService);

            $centralSyncRequestValidator = new SyncParamsValidator();
            $serviceManager->register(SyncParamsValidator::SERVICE_ID, $centralSyncRequestValidator);

            $vmVersionChecker = new VmVersionChecker();
            $serviceManager->register(VmVersionChecker::SERVICE_ID, $vmVersionChecker);

            $this->setVersion('5.5.0');
        }

        if ($this->isVersion('5.5.0')) {
            $vmVersionChecker = new VmVersionChecker();
            $this->getServiceManager()->register(VmVersionChecker::SERVICE_ID, $vmVersionChecker);

            $this->setVersion('5.5.1');
        }

        $this->skip('5.5.1', '5.5.4');

        if ($this->isVersion('5.5.4')) {
            if ($this->getServiceManager()->has(DataSyncHistoryByOrgIdService::SERVICE_ID)) {
                /** @var DataSyncHistoryByOrgIdService $service */
                $service = $this->getServiceManager()->get(DataSyncHistoryByOrgIdService::SERVICE_ID);
                $persistence = $service->getPersistence();
                $schemaManager = $persistence->getSchemaManager();
                $fromSchema = $schemaManager->createSchema();
                $toSchema = clone $fromSchema;
                $table = $toSchema->getTable(DataSyncHistoryByOrgIdService::SYNC_TABLE);
                if ($table->hasColumn(DataSyncHistoryByOrgIdService::SYNC_ORG_ID)) {
                    $table->changeColumn(
                        DataSyncHistoryByOrgIdService::SYNC_ORG_ID,
                        ['type' => Type::getType('string'), 'length' => 255, 'notnull' => true, 'default' => '']
                    );
                    $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $toSchema);
                    foreach ($queries as $query) {
                        $persistence->exec($query);
                    }
                }
            }

            $this->setVersion('5.5.5');
        }

        if ($this->isVersion('5.5.5')) {
            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(RequestStatsEvent::class, [ClientSyncLogListener::SERVICE_ID, 'syncRequestStats']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $syncClient = $this->getServiceManager()->get(SynchronisationClient::SERVICE_ID);
            $syncClient->setOption(SynchronisationClient::OPTION_STATS_HANDLER, new ConnectionStatsHandler());
            $syncClient->setOption(SynchronisationClient::OPTION_EXPECTED_UPLOAD_SPEED, 1);
            $syncClient->setOption(SynchronisationClient::OPTION_EXPECTED_DOWNLOAD_SPEED, 1);
            $this->getServiceManager()->register(SynchronisationClient::SERVICE_ID, $syncClient);

            $this->setVersion('5.6.0');
        }

        $this->skip('5.6.0', '6.1.0');

        if ($this->isVersion('6.1.0')) {
            $vmIdentifierService = new VmIdentifierService([]);
            $this->getServiceManager()->register(VmIdentifierService::SERVICE_ID, $vmIdentifierService);

            $this->setVersion('6.2.0');
        }

        if ($this->isVersion('6.2.0')) {
            $this->getServiceManager()->register(
                SyncManagerTreeService::SERVICE_ID,
                new SyncManagerTreeService([])
            );
            $this->setVersion('6.4.0');
        }
        $this->skip('6.4.0', '6.4.1');
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addColumnToDeliveryLog()
    {
        /** @var \common_persistence_SqlPersistence $persistence */
        $persistence = \common_persistence_Manager::getPersistence('default');
        $schemaManager = $persistence->getSchemaManager();
        $fromSchema = $schemaManager->createSchema();
        $toSchema = clone $fromSchema;

        $table = $toSchema->getTable(RdsDeliveryLogService::TABLE_NAME);
        if (!$table->hasColumn(EnhancedDeliveryLogService::COLUMN_IS_SYNCED)) {
            $table->addColumn(EnhancedDeliveryLogService::COLUMN_IS_SYNCED, 'integer', ['notnull' => true, 'length' => 1, 'default' => 0]);
            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $toSchema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function dropColumnIsSynced()
    {
        /** @var \common_persistence_SqlPersistence $persistence */
        $persistence = \common_persistence_Manager::getPersistence('default');
        $schemaManager = $persistence->getSchemaManager();
        $fromSchema = $schemaManager->createSchema();
        $toSchema = clone $fromSchema;

        $table = $toSchema->getTable(ResultSyncHistoryService::SYNC_RESULT_TABLE);
        if ($table->hasColumn(ResultSyncHistoryService::SYNC_LOG_SYNCED)) {
            $table->dropColumn(ResultSyncHistoryService::SYNC_LOG_SYNCED);
            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $toSchema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }
        }
    }
}
