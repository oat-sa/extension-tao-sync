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
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoSync\model\synchronizer\administrator\AdministratorSynchronizer;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizer;
use oat\taoSync\model\synchronizer\eligibility\EligibilitySynchronizer;
use oat\taoSync\model\synchronizer\proctor\ProctorSynchronizer;
use oat\taoSync\model\synchronizer\testcenter\RdfTestCenterSynchronizer;
use oat\taoSync\model\synchronizer\testcenter\TestCenterSynchronizer;
use oat\taoSync\model\synchronizer\testtaker\RdfTestTakerSynchronizer;
use oat\taoSync\model\synchronizer\testtaker\TestTakerSynchronizer;
use oat\taoSync\model\SyncService;

class RegisterSyncService extends InstallAction
{
    public function __invoke($params)
    {
        $options = array(
            SyncService::OPTION_SYNCHRONIZERS => array(
                TestCenterSynchronizer::SYNC_ID => new RdfTestCenterSynchronizer(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                'administrator' => new AdministratorSynchronizer(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                'proctor' => new ProctorSynchronizer(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                TestTakerSynchronizer::SYNC_ID => new RdfTestTakerSynchronizer(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                'eligibility' => new EligibilitySynchronizer(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                )),
                'delivery' => new DeliverySynchronizer(array(
                    AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                        TaoOntology::PROPERTY_UPDATED_AT
                    )
                ))
            )
        );

        $this->registerService(SyncService::SERVICE_ID, new SyncService($options));
        return \common_report_Report::createSuccess('SyncService successfully registered.');
    }

}