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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\test\server;

use oat\generis\test\TestCase;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\SyncService;
use oat\taoSync\model\testCenter\Domain\TestCenter;
use oat\taoSync\model\testCenter\SyncManagerTreeService;
use oat\taoSync\model\testCenter\TestCenterService;

class SyncManagerTreeServiceTest extends TestCase
{
    public function testCreateTestCenterDomain()
    {
        $testCenterMock = $this->getResourceMock();

        $testCenterServiceMock = $this->getMockBuilder(TestCenterService::class)
            ->setMethods(['getTestCenterOrganisationId'])
            ->getMock();

        $testCenterServiceMock
            ->method('getTestCenterOrganisationId')
            ->with($testCenterMock)
            ->willReturn('orgId');

        $serviceLocatorMock = $this->getServiceLocatorMock([TestCenterService::SERVICE_ID => $testCenterServiceMock]);

        $serviceMock = $this->getMockBuilder(SyncManagerTreeService::class)
            ->setMethods(['getResource', 'getOnePropertyValue', 'getServiceLocator'])
            ->getMock();

        $serviceMock
            ->method('getResource')
            ->with('testCenterUri')
            ->willReturn($testCenterMock);

        $serviceMock->method('getServiceLocator')->willReturn($serviceLocatorMock);

        $domain = $serviceMock->createTestCenterDomain('testCenterUri');

        $this->assertSame($testCenterMock, $domain->getTestCenter());
        $this->assertEquals('orgId', $domain->getOrganisationId());
    }

    public function testGetUserTestCenters()
    {
        $userMock = $this->getResourceMock();

        $serviceMock = $this->getMockBuilder(SyncManagerTreeService::class)
            ->setMethods(['getProperty'])
            ->getMock();

        $property = $this->getPropertyMock();

        $serviceMock
            ->method('getProperty')
            ->with(SyncService::PROPERTY_ASSIGNED_SYNC_USER)
            ->willReturn($property);

        $userMock
            ->expects($this->once())
            ->method('getPropertyValues')
            ->with($property)
            ->willReturn([1, 2, 3]);

        $values = $serviceMock->getUserTestCenters($userMock);

        $this->assertEquals([1, 2, 3], $values);
    }

    public function testGetAssignedSyncManagers()
    {
        $userServiceMock = $this->createMock(\tao_models_classes_UserService::class);

        $serviceLocatorMock = $this->getServiceLocatorMock(
            [\tao_models_classes_UserService::SERVICE_ID => $userServiceMock]
        );

        $serviceMock = $this->getMockBuilder(SyncManagerTreeService::class)
            ->setMethods(['getServiceLocator'])
            ->getMock();

        $testCenter = $this->getResourceMock();
        $testCenterDomain = new TestCenter($testCenter, 'org');

        $serviceMock->method('getServiceLocator')
            ->willReturn($serviceLocatorMock);

        $userServiceMock->expects($this->once())
            ->method('getAllUsers')
            ->with([], [SyncService::PROPERTY_ASSIGNED_SYNC_USER => $testCenter])
            ->willReturn([1, 2, 3]);

        $this->assertEquals([1, 2, 3], $serviceMock->getAssignedSyncManagers($testCenterDomain));
    }

    public function testUnassignAllUsersFromTestCenter()
    {
        $userMock = $this->getResourceMock();
        $userMock2 = $this->getResourceMock();
        $userMock3 = $this->getResourceMock();

        $testCenter = $this->getResourceMock();
        $testCenterDomain = new TestCenter($testCenter, 'org');

        $serviceMock = $this->getMockBuilder(SyncManagerTreeService::class)
            ->setMethods(['getProperty', 'getAssignedSyncManagers'])
            ->getMock();

        $property = $this->getPropertyMock();
        $property2 = $this->getPropertyMock();

        $serviceMock
            ->method('getProperty')
            ->withConsecutive(
                [SyncService::PROPERTY_ASSIGNED_SYNC_USER],
                [TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY]
            )
            ->will($this->onConsecutiveCalls($property, $property2));

         $serviceMock
             ->method('getAssignedSyncManagers')
             ->with($testCenterDomain)
             ->willReturn([$userMock, $userMock2, $userMock3]);

        $userMock->expects($this->exactly(2))
            ->method('removePropertyValue')
            ->withConsecutive(
                [$property, $testCenter],
                [$property2, 'org']
            )
            ->willReturn(true);

        $userMock2->expects($this->exactly(2))
            ->method('removePropertyValue')
            ->withConsecutive(
                [$property, $testCenter],
                [$property2, 'org']
            )
            ->willReturn(true);

        $userMock3->expects($this->exactly(2))
            ->method('removePropertyValue')
            ->withConsecutive(
                [$property, $testCenter],
                [$property2, 'org']
            )
            ->willReturn(true);

        $this->assertTrue($serviceMock->unassignAllUsersFromTestCenter($testCenterDomain));
    }

    public function testUnassignOthersUsersFromTestCenter()
    {
        $userMock = $this->getResourceMock();
        $userMock2 = $this->getResourceMock();
        $userMock3 = $this->getResourceMock();
        $userMock->method('getUri')->willReturn('user');
        $userMock2->method('getUri')->willReturn('user2');
        $userMock3->method('getUri')->willReturn('user3');

        $testCenter = $this->getResourceMock();
        $testCenterDomain = new TestCenter($testCenter, 'org');

        $serviceMock = $this->getMockBuilder(SyncManagerTreeService::class)
            ->setMethods(['getProperty', 'getAssignedSyncManagers'])
            ->getMock();

        $property = $this->getPropertyMock();
        $property2 = $this->getPropertyMock();

        $serviceMock
            ->method('getProperty')
            ->withConsecutive(
                [SyncService::PROPERTY_ASSIGNED_SYNC_USER],
                [TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY]
            )
            ->will($this->onConsecutiveCalls($property, $property2));

        $serviceMock
            ->method('getAssignedSyncManagers')
            ->with($testCenterDomain)
            ->willReturn([$userMock, $userMock2, $userMock3]);

        $userMock->expects($this->never())
            ->method('removePropertyValue')
            ->willReturn(true);

        $userMock2->expects($this->exactly(2))
            ->method('removePropertyValue')
            ->withConsecutive(
                [$property, $testCenter],
                [$property2, 'org']
            )
            ->willReturn(true);

        $userMock3->expects($this->exactly(2))
            ->method('removePropertyValue')
            ->withConsecutive(
                [$property, $testCenter],
                [$property2, 'org']
            )
            ->willReturn(true);
        $this->assertTrue($serviceMock->unassignOthersUsersFromTestCenter($testCenterDomain, $userMock));
    }

    public function testSaveReversedValues()
    {
        $userMock = $this->getResourceMock();
        $testCenter = $this->getResourceMock();
        $testCenterDomain = new TestCenter($testCenter, 'org');

        $property = $this->getPropertyMock();
        $property2 = $this->getPropertyMock();

        $serviceMock = $this->getMockBuilder(SyncManagerTreeService::class)
            ->setMethods(['getProperty', 'getAssignedSyncManagers', 'unassignOthersUsersFromTestCenter'])
            ->getMock();

        $serviceMock
            ->method('getProperty')
            ->withConsecutive(
                [SyncService::PROPERTY_ASSIGNED_SYNC_USER],
                [TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY]
            )
            ->will($this->onConsecutiveCalls($property, $property2));

        $serviceMock->expects($this->once())
            ->method('unassignOthersUsersFromTestCenter')
            ->with($testCenterDomain, $userMock)
            ->willReturn(true);

        $userMock->expects($this->exactly(2))
            ->method('removePropertyValues')
            ->withConsecutive([$property ], [$property2])
            ->willReturn(true);

        $userMock->expects($this->exactly(2))
            ->method('setPropertyValue')
            ->withConsecutive(
                [$property, $testCenter],
                [$property2, 'org']
            )
            ->willReturn(true);

        $serviceMock->saveReversedValues($testCenterDomain, $userMock);
    }

    private function getResourceMock()
    {
        return $this->getMockBuilder(\core_kernel_classes_Resource::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getPropertyMock()
    {
        return $this->getMockBuilder(\core_kernel_classes_Property::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
