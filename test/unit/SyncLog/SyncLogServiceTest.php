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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\test\unit\SyncLog;

use oat\generis\test\TestCase;
use oat\oatbox\extension\script\MissingOptionException;
use oat\taoSync\model\SyncLog\Storage\SyncLogStorageInterface;
use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogService;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;
use oat\generis\test\MockObject;

class SyncLogServiceTest extends TestCase
{
    const OPTION_STORAGE = 'test_storage';

    /**
     * @var SyncLogServiceInterface
     */
    private $object;

    /**
     * @var SyncLogStorageInterface|MockObject
     */
    private $storageMock;

    protected function setUp()
    {
        parent::setUp();

        $this->storageMock = $this->createMock(SyncLogStorageInterface::class);
        $serviceLocatorMock = $this->getServiceLocatorMock([
            self::OPTION_STORAGE => $this->storageMock
        ]);

        $this->object = new SyncLogService([
            SyncLogService::OPTION_STORAGE => self::OPTION_STORAGE
        ]);
        $this->object->setServiceLocator($serviceLocatorMock);
    }

    public function testConstructorFailsWithoutStorageOption()
    {
        $this->expectException(MissingOptionException::class);

        new SyncLogService([]);
    }

    public function testCreateReturnsEntityWithId()
    {
        $expectedEntityId = 555;
        $syncLogEntity = new SyncLogEntity(
            1,
            'BOX_ID',
            '111',
            [], SyncLogEntity::STATUS_IN_PROGRESS,
            \common_report_Report::createInfo(),
            new \DateTime()
        );
        $this->storageMock->expects($this->once())
            ->method('create')
            ->willReturn($expectedEntityId);

        $this->assertNull($syncLogEntity->getId(), "Entity id must be null until it's stored.");
        
        $syncLogEntity = $this->object->create($syncLogEntity);
        $this->assertEquals($expectedEntityId, $syncLogEntity->getId(), 'Entity id must be as expected.');
    }
}

