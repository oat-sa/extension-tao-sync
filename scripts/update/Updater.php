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

use oat\generis\model\OntologyRdfs;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\accessControl\func\AccessRule;
use oat\tao\model\accessControl\func\AclProxy;
use oat\oatbox\filesystem\FileSystem;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\user\import\UserCsvImporterFactory;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\controller\HandShake;
use oat\taoSync\model\import\SyncUserCsvImporter;
use oat\taoSync\model\ResultService;
use oat\taoSync\model\server\HandShakeServerService;
use oat\taoSync\model\SynchronizeAllTaskBuilderService;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizer;
use oat\taoSync\model\SyncService;
use oat\taoSync\model\User\HandShakeClientService;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;
use oat\taoSync\model\ui\FormFieldsService;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeResult;

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

        $this->skip('0.14.2','0.15.0');

        if ($this->isVersion('0.15.0')) {
            $service = $this->getServiceManager()->get(SyncService::SERVICE_ID);
            $options = $service->getOptions();
            if (
                isset($options[SyncService::OPTION_SYNCHRONIZERS])
                && isset($options[SyncService::OPTION_SYNCHRONIZERS][DeliverySynchronizer::SYNC_DELIVERY])
            ) {
                /** @var ConfigurableService $deliverySynchronizer */
                $deliverySynchronizer = $options[SyncService::OPTION_SYNCHRONIZERS][DeliverySynchronizer::SYNC_DELIVERY];
                if ($deliverySynchronizer->hasOption(AbstractResourceSynchronizer::OPTIONS_FIELDS)) {
                    $deliveryFields = $deliverySynchronizer->getOption(AbstractResourceSynchronizer::OPTIONS_FIELDS);
                    if (!in_array(OntologyRdfs::RDFS_SUBCLASSOF, $deliveryFields)) {
                        $deliveryFields[] = OntologyRdfs::RDFS_SUBCLASSOF;
                        $deliverySynchronizer->setOption(AbstractResourceSynchronizer::OPTIONS_FIELDS, $deliveryFields);
                        $options[SyncService::OPTION_SYNCHRONIZERS][DeliverySynchronizer::SYNC_DELIVERY] = $deliverySynchronizer;
                        $service->setOptions($options);
                        $this->getServiceManager()->register(SyncService::SERVICE_ID, $service);
                    }
                }


            }
            $this->setVersion('0.15.1');
        }
    }

}
