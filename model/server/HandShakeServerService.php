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
     * @param $userIdentifier User's Uri
     * @return HandShakeServerResponse
     * @throws InvalidRoleForSync
     * @throws \common_exception_BadRequest
     */
    public function execute($userIdentifier)
    {
        $user = $this->getResource($userIdentifier);
        if (!in_array(SyncService::TAO_SYNC_ROLE, $user->getPropertyValues($this->getProperty(GenerisRdf::PROPERTY_USER_ROLES)))) {
            throw new InvalidRoleForSync('User does not have the ' . SyncService::TAO_SYNC_ROLE . ' role.');
        }

        $generator = $this->getGeneratorOauth();
        $generator->__invoke([]);

        $user->editPropertyValues(
            new \core_kernel_classes_Property(SyncService::PROPERTY_CONSUMER_USER),
            $generator->getCreatedConsumer()
        );

        $this->triggerEvent($user);

        return new HandShakeServerResponse($generator->getCreatedConsumer(), $user, $this->getFormatter()) ;
    }

    /**
     * @return core_kernel_users_Service
     */
    protected function getUsersService()
    {
        return core_kernel_users_Service::singleton();
    }

    /**
     * @return GenerateOauthCredentials
     */
    protected function getGeneratorOauth()
    {
        $generator = new GenerateOauthCredentials();
        $this->propagate($generator);

        return $generator;
    }

    /**
     * @param $user
     */
    protected function triggerEvent($user)
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(new HandShakeServerEvent($user));
    }

    /**
     * @return \oat\taoSync\model\formatter\SynchronizerFormatter
     * @throws \common_exception_BadRequest
     */
    protected function getFormatter()
    {
        /** @var SyncService $syncService */
        $syncService = $this->getServiceLocator()->get(SyncService::SERVICE_ID);
        $synchronizer = $syncService->getSynchronizer('administrator');

        return $synchronizer->getFormatter();
    }
}
