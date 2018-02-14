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

<<<<<<< HEAD
use oat\tao\scripts\update\OntologyUpdater;
=======
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
use oat\taoSync\model\ResultService;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizerService;
use oat\taoSync\model\synchronizer\delivery\RdfDeliverySynchronizer;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizer;
use oat\taoSync\scripts\tool\SynchronizeData;
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
>>>>>>> 6d9eded1d90a181843d197bcebdc5097abd60a5c

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
<<<<<<< HEAD
        $this->skip('0.0.1','0.0.2');

        if ($this->isVersion('0.0.2')) {

            // include the Sync master role
            OntologyUpdater::syncModels();
            $this->setVersion('0.1.0');
        }
=======
        $this->setVersion('0.1.0');
>>>>>>> 6d9eded1d90a181843d197bcebdc5097abd60a5c
    }
}
