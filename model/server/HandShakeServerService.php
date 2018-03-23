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

namespace oat\taoSync\model\server;

use core_kernel_users_Service;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\SyncService;
use oat\taoSync\scripts\tool\oauth\GenerateOauthCredentials;

class HandShakeServerService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/handShakeServer';

    /**
     * @param $userIdentifier
     * @throws \core_kernel_persistence_Exception
     * @throws \Exception
     */
    public function execute($userIdentifier)
    {
        $user = core_kernel_users_Service::singleton()->getOneUser($userIdentifier);
        if (!in_array(SyncService::TAO_SYNC_ROLE, $user->getPropertyValues($this->getProperty(GenerisRdf::PROPERTY_USER_ROLES)))) {
            throw new \Exception('User does not have the '. SyncService::TAO_SYNC_ROLE . ' role.');
        }

        $generator = new GenerateOauthCredentials();
        $this->propagate($generator);
        $generator->__invoke([]);

        $user->editPropertyValues(
            new \core_kernel_classes_Property(SyncService::PROPERTY_CONSUMER_USER),
            $generator->getCreatedConsumer()
        );

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new HandShakeServerEvent($user));

        return new HandShakeServerResponse($generator->getCreatedConsumer(), $user) ;
    }
}