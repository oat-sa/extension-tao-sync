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

namespace oat\taoSync\test\server;

use core_kernel_classes_Resource;
use core_kernel_users_Service;
use oat\oatbox\event\EventManager;
use oat\taoSync\model\formatter\SynchronizerFormatter;
use oat\taoSync\model\server\HandShakeServerResponse;
use oat\taoSync\model\server\HandShakeServerService;
use oat\taoSync\model\synchronizer\Synchronizer;
use oat\taoSync\model\SyncService;
use oat\taoSync\scripts\tool\oauth\GenerateOauthCredentials;
use Zend\ServiceManager\ServiceLocatorInterface;

class HandShakeServerServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testHandShakeReceive()
    {
        $service = $this->getService([SyncService::TAO_SYNC_ROLE]);
        $response = $service->execute('123456');

        $this->assertInstanceOf(HandShakeServerResponse::class, $response);
    }

    /**
     * @expectedException \oat\taoSync\model\server\InvalidRoleForSync
     */
    public function testHandShakeNotASyncManger()
    {
        $service = $this->getService();
        $response = $service->execute('123456');

        $this->assertInstanceOf(HandShakeServerResponse::class, $response);
    }

    /**
     * @param array $roles
     * @return HandShakeServerService
     */
    protected function getService($roles = [])
    {
        $service = $this->getMockBuilder(HandShakeServerService::class)
            ->setMethods(['getUsersService', 'getGeneratorOauth'])->getMockForAbstractClass();

        $service
            ->method('getUsersService')
            ->willReturn($this->mockUsersService($this->mockResource($roles)));

        $service
            ->method('getGeneratorOauth')
            ->willReturn($this->mockGenerator());

        $serviceLocator = $this->getMockForAbstractClass(ServiceLocatorInterface::class);
        $serviceLocator
            ->method('get')
            ->will($this->onConsecutiveCalls(
                $this->mockEventManger(),
                $this->mockSyncService()

            ));
        $service->setServiceLocator($serviceLocator);


        return $service;
    }

    protected function mockGenerator()
    {
        $service = $this->getMockBuilder(GenerateOauthCredentials::class)
            ->setMethods(['__invoke', 'getCreatedConsumer'])->disableOriginalConstructor()->getMock();
        $service
            ->method('getCreatedConsumer')
            ->willReturn($this->mockResource());

        return $service;
    }

    protected function mockSyncService()
    {
        $synchronizer = $this->getMockForAbstractClass(Synchronizer::class);
        $synchronizer
            ->method('getFormatter')
            ->willReturn($this->getMockForAbstractClass(SynchronizerFormatter::class));

        $sync = $this->getMockBuilder(SyncService::class)->disableOriginalConstructor()->getMock();
        $sync
            ->method('getSynchronizer')
            ->willReturn($synchronizer);

        return $sync;
    }

    protected function mockUsersService($user)
    {
        $service = $this->getMockBuilder(core_kernel_users_Service::class)->disableOriginalConstructor()->getMock();
        $service
            ->method('getOneUser')
            ->willReturn($user);

        return $service;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockResource($roles = [])
    {
        $mock = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setMethods(['getPropertyValues', 'editPropertyValues'])
            ->disableOriginalConstructor()->getMock();

        $mock->method('getPropertyValues')->willReturn($roles);

        return $mock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockEventManger()
    {
        return
            $this->getMockBuilder(EventManager::class)
            ->setMethods(['trigger'])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
