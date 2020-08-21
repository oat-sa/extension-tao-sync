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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\taoSync\test\unit;

use common_persistence_KeyValuePersistence;
use common_persistence_Manager;
use core_kernel_classes_Resource;
use oat\generis\model\data\event\ResourceDeleted;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoSync\model\EntityChecksumCacheService;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use PHPUnit_Framework_MockObject_MockObject;

class EntityChecksumCacheServiceTest extends TestCase
{
    /** @var EntityChecksumCacheService */
    private $service;

    /** @var common_persistence_KeyValuePersistence|PHPUnit_Framework_MockObject_MockObject */
    private $persistenceMock;

    public function setUp(): void
    {
        $this->persistenceMock = $this->createMock(common_persistence_KeyValuePersistence::class);
        $persistenceManagerMock = $this->createMock(common_persistence_Manager::class);
        $persistenceManagerMock->method('getPersistenceById')->willReturn($this->persistenceMock);
        $serviceLocatorMock = $this->getServiceLocatorMock([
            common_persistence_Manager::SERVICE_ID => $persistenceManagerMock,
        ]);
        $this->service = new EntityChecksumCacheService([
            'persistence' => 'default'
        ]);
        $this->service->setServiceLocator($serviceLocatorMock);
    }

    public function testGet_WhenPersistenceNotConfigured_ThenExceptionThrown(): void
    {
        $this->service->setOptions([]);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->get('TEST_URI');
    }

    public function testGet_WhenPersistenceConfigured_ThenFoundValueIsReturned(): void
    {
        $cachedChecksum = 'CACHED_CHECKSUM';
        $this->persistenceMock->expects($this->once())->method('get')->willReturn($cachedChecksum);
        $this->assertSame($cachedChecksum, $this->service->get('TEST_URI'));
    }

    public function testSet_WhenEntityIdAndChecksumProvided_ThenStoredInPersistence(): void
    {
        $this->persistenceMock->expects($this->once())->method('set')->willReturn(true);
        $this->service->set('TEST_URI', 'TEST_CHECKSUM');
    }

    public function testDelete_WhenEntityIdProvided_ThenChecksumRemovedFromPersistence()
    {
        $this->persistenceMock->expects($this->once())->method('del');
        $this->service->delete('TEST_URI');
    }

    public function testEntityDeleted_WhenResourceDeletedTriggered_ThenChecksumDeletedFromPersistence()
    {
        $this->persistenceMock->expects($this->once())->method('del');
        $this->service->entityDeleted(new ResourceDeleted('TEST_URI'));
    }
}
