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

use oat\generis\model\data\event\ResourceCreated;
use oat\oatbox\event\EventManager;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\Entity;
use oat\taoSync\model\listener\ListenerService;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizerService;
use oat\taoSync\model\synchronizer\delivery\RdfDeliverySynchronizer;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizer;
use oat\taoSync\scripts\tool\SyncDeliveryData;
use oat\tao\model\TaoOntology;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoSync\model\synchronizer\eligibility\EligibilitySynchronizer;
use oat\taoSync\model\synchronizer\eligibility\RdfEligibilitySynchronizer;
use oat\taoSync\model\synchronizer\testcenter\RdfTestCenterSynchronizer;
use oat\taoSync\model\synchronizer\user\administrator\RdfAdministratorSynchronizer;
use oat\taoSync\model\synchronizer\user\proctor\RdfProctorSynchronizer;
use oat\taoSync\model\synchronizer\user\testtaker\RdfTestTakerSynchronizer;
use oat\taoSync\model\synchronizer\user\testtaker\TestTakerSynchronizer;
use oat\taoSync\model\synchronizer\user\administrator\AdministratorSynchronizer;
use oat\taoSync\model\synchronizer\user\proctor\ProctorSynchronizer;
use oat\taoSync\model\synchronizer\testcenter\TestCenterSynchronizer;
use oat\taoSync\model\SyncService;

/**
 * Class Updater
 *
 * @author Moyon Camille <camille@taotesting.com>
 */
class Updater extends \common_ext_ExtensionUpdater
{
    public function update($initialVersion)
    {
        if ($this->isVersion('0.0.1')) {
            OntologyUpdater::syncModels();

            $options = array(
                SyncService::OPTION_CHUNK_SIZE => SyncService::DEFAULT_CHUNK_SIZE,
                SyncService::OPTION_SYNCHRONIZERS => array(
                    TestCenterSynchronizer::SYNC_ID => new RdfTestCenterSynchronizer(array(
                        AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                            TaoOntology::PROPERTY_UPDATED_AT,
                            Entity::CREATED_AT,
                        )
                    )),
                    AdministratorSynchronizer::SYNC_ADMINISTRATOR => new RdfAdministratorSynchronizer(array(
                        AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                            TaoOntology::PROPERTY_UPDATED_AT,
                            Entity::CREATED_AT,
                        )
                    )),
                    ProctorSynchronizer::SYNC_PROCTOR => new RdfProctorSynchronizer(array(
                        AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                            TaoOntology::PROPERTY_UPDATED_AT,
                            Entity::CREATED_AT,
                        )
                    )),
                    TestTakerSynchronizer::SYNC_ID => new RdfTestTakerSynchronizer(array(
                        AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                            TaoOntology::PROPERTY_UPDATED_AT,
                            Entity::CREATED_AT,
                        )
                    )),
                    EligibilitySynchronizer::SYNC_ELIGIBILITY => new RdfEligibilitySynchronizer(array(
                        AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                            TaoOntology::PROPERTY_UPDATED_AT,
                            Entity::CREATED_AT,
                        )
                    )),
                    DeliverySynchronizer::SYNC_DELIVERY => new RdfDeliverySynchronizer(array(
                        AbstractResourceSynchronizer::OPTIONS_FIELDS => array(
                            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                            'http://www.w3.org/2000/01/rdf-schema#label',
                            'http://www.tao.lu/Ontologies/TAODelivery.rdf#DeliveryResultServer',
                            'http://www.tao.lu/Ontologies/TAODelivery.rdf#Maxexec ',
                            'http://www.tao.lu/Ontologies/TAODelivery.rdf#DisplayOrder',
                            'http://www.tao.lu/Ontologies/TAODelivery.rdf#AccessSettings',
                            'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodEnd',
                            'http://www.tao.lu/Ontologies/TAODelivery.rdf#CustomLabel',
                            'http://www.tao.lu/Ontologies/TAODelivery.rdf#ProctorAccessible',
                            'http://www.tao.lu/Ontologies/TAODelivery.rdf#PeriodStart',
                        )
                    ))
                )
            );

            $this->getServiceManager()->register(SyncService::SERVICE_ID, new SyncService($options));

            $this->getServiceManager()->register(ListenerService::SERVICE_ID, new ListenerService());
            $this->getServiceManager()->register(SynchronisationClient::SERVICE_ID, new SynchronisationClient());
            $this->getServiceManager()->register(DeliverySynchronizerService::SERVICE_ID, new DeliverySynchronizerService());

            /** @var PublishingService $service */
            $service = $this->getServiceManager()->get(PublishingService::SERVICE_ID);
            $actions = $service->getOption(PublishingService::OPTIONS_ACTIONS);
            if (!in_array(SyncDeliveryData::class, $actions)) {
                $actions[] = SyncDeliveryData::class;
                $service->setOption(PublishingService::OPTIONS_ACTIONS, $actions);
                $this->getServiceManager()->register(PublishingService::SERVICE_ID, $service);
            }

            // Attach events
            /** @var EventManager $eventManager */
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->attach(DeliveryCreatedEvent::class, [ListenerService::SERVICE_ID, 'listen']);
            $eventManager->attach(DeliveryUpdatedEvent::class, [ListenerService::SERVICE_ID, 'listen']);
            $eventManager->attach(ResourceCreated::class, [ListenerService::SERVICE_ID, 'listen']);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            /** @var FileSystemService $fileSystemService */
            $fileSystemService = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
            $fileSystemService->createFileSystem('synchronisation');
            $this->getServiceManager()->register(FileSystemService::SERVICE_ID, $fileSystemService);

            $this->setVersion('0.2.0');
        }
    }
}