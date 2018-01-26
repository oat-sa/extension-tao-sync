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

use oat\oatbox\extension\InstallAction;
use oat\tao\model\TaoOntology;
use oat\taoSync\model\custom\synchronizer\AdministratorByOrganisationID;
use oat\taoSync\model\custom\synchronizer\EligibilityByOrganisationId;
use oat\taoSync\model\custom\synchronizer\ProctorByOrganisationID;
use oat\taoSync\model\custom\synchronizer\TestCenterByOrganisationID;
use oat\taoSync\model\custom\synchronizer\TestTakerByOrganisationID;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoSync\model\synchronizer\delivery\RdfDeliverySynchronizer;
use oat\taoSync\model\synchronizer\eligibility\EligibilitySynchronizer;
use oat\taoSync\model\synchronizer\user\testtaker\TestTakerSynchronizer;
use oat\taoSync\model\synchronizer\user\administrator\AdministratorSynchronizer;
use oat\taoSync\model\synchronizer\user\proctor\ProctorSynchronizer;
use oat\taoSync\model\synchronizer\testcenter\TestCenterSynchronizer;
use oat\taoSync\model\SyncService;

/**
 * Class RegisterSyncService
 *
 * sudo -u www-data php index.php '\oat\taoSync\scripts\install\RegisterSyncServiceByOrgId'
 *
 * @package oat\taoSync\scripts\install
 */
class RegisterSyncServiceByOrgId extends InstallAction
{
    public function __invoke($params)
    {
        $options = array(
            SyncService::OPTION_SYNCHRONIZERS => array(
                TestCenterSynchronizer::SYNC_ID => new TestCenterByOrganisationID(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                AdministratorSynchronizer::SYNC_ADMINISTRATOR => new AdministratorByOrganisationID(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                ProctorSynchronizer::SYNC_PROCTOR => new ProctorByOrganisationID(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                TestTakerSynchronizer::SYNC_ID => new TestTakerByOrganisationID(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                EligibilitySynchronizer::SYNC_ELIGIBILITY => new EligibilityByOrganisationId(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                'delivery' => new RdfDeliverySynchronizer(array(
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

        $this->registerService(SyncService::SERVICE_ID, new SyncService($options));
        return \common_report_Report::createSuccess('SyncService successfully registered.');
    }

}