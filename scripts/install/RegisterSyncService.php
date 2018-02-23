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

namespace oat\taoSync\scripts\install;

use oat\generis\model\OntologyRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\extension\InstallAction;
use oat\tao\model\TaoOntology;
use oat\taoDelivery\model\fields\DeliveryFieldsService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoProctoring\model\ProctorService;
use oat\taoResultServer\models\classes\implementation\OntologyService;
use oat\taoSync\model\Entity;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoSync\model\synchronizer\delivery\RdfDeliverySynchronizer;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizer;
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
 * Class RegisterSyncService
 *
 * Register the sync service and all synchronizers needed for synchronisation of entity
 *
 * @package oat\taoSync\scripts\install
 */
class RegisterSyncService extends InstallAction
{
    public function __invoke($params)
    {
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
                        OntologyRdf::RDF_TYPE,
                        OntologyRdfs::RDFS_LABEL,
                        OntologyService::PROPERTY_RESULT_SERVER,
                        DeliveryContainerService::PROPERTY_MAX_EXEC,
                        DeliveryAssemblyService::PROPERTY_DELIVERY_DISPLAY_ORDER_PROP,
                        DeliveryContainerService::PROPERTY_ACCESS_SETTINGS,
                        DeliveryAssemblyService::PROPERTY_END,
                        DeliveryFieldsService::PROPERTY_CUSTOM_LABEL,
                        ProctorService::ACCESSIBLE_PROCTOR,
                        DeliveryAssemblyService::PROPERTY_START,
                    )
                ))
            )
        );

        $this->registerService(SyncService::SERVICE_ID, new SyncService($options));
        return \common_report_Report::createSuccess('SyncService successfully registered.');
    }

}