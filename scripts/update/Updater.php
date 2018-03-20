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

use oat\tao\scripts\update\OntologyUpdater;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\server\HandShakeServerService;
use oat\taoSync\model\User\HandShakeService;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;
use oat\taoSync\model\ui\FormFieldsService;

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
            $handShakeService = new HandShakeService([
                HandShakeService::OPTION_ROOT_URL => 'http://tao.dev/',
                HandShakeService::OPTION_REMOTE_AUTH_URL => 'http://tao.dev/taoSync/HandShake'
            ]);

            $this->getServiceManager()->register(HandShakeService::SERVICE_ID, $handShakeService);

            $handShakeServerService = new HandShakeServerService([]);

            $this->getServiceManager()->register(HandShakeServerService::SERVICE_ID, $handShakeServerService);

            $this->setVersion('0.10.0');
        }
    }
}
