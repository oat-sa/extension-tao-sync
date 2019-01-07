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

namespace oat\taoSync\test\unit\SyncLog\Storage;


use oat\oatbox\extension\script\MissingOptionException;
use oat\taoSync\model\SyncLog\Storage\RdsSyncLogStorage;
use oat\generis\test\TestCase;
use oat\taoSync\model\SyncLog\SyncLogEntity;

/**
 * Class RdsSyncLogStorageTest
 */
class RdsSyncLogStorageTest extends TestCase
{
    const PERSISTENCE_ID = 'PERSISTENCE_ID';

    /**
     * @var RdsSyncLogStorage
     */
    private $object;

    /**
     * @var \common_persistence_Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistenceManagerMock;

    /**
     * @var \common_persistence_SqlPersistence|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistenceMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->persistenceMock = $this->createMock(\common_persistence_SqlPersistence::class);

        $this->persistenceManagerMock = $this->createMock(\common_persistence_Manager::class);
        $this->persistenceManagerMock ->method('getPersistenceById')
            ->willReturn($this->persistenceMock);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            \common_persistence_Manager::SERVICE_ID => $this->persistenceManagerMock
        ]);

        $this->object = new RdsSyncLogStorage([
            RdsSyncLogStorage::OPTION_PERSISTENCE_ID => self::PERSISTENCE_ID
        ]);
        $this->object->setServiceLocator($serviceLocatorMock);
    }

    /**
     * Test that constructor throws exception when "persistenceId" option is missing.
     *
     * @throws MissingOptionException
     */
    public function testConstructWithoutRequiredOption()
    {
        $this->expectException(MissingOptionException::class);

        new RdsSyncLogStorage([]);
    }

    /**
     * Test getPersistence method.
     */
    public function testGetPersistence()
    {
        $persistence = $this->object->getPersistence();

        $this->assertInstanceOf(\common_persistence_SqlPersistence::class, $persistence, 'Returned persistence must be an object of expected type.');
    }

    /**
     * Test create method.
     */
    public function testCreate()
    {
        $tableName = 'synchronisation_log';
        $syncId = 'SYNC_ID';
        $boxId = 'BOX_ID';
        $orgId = 'ORG_ID';
        $data = ['ENTITY_TYPE' => ['ACTION' => 'AMOUNT']];
        $status = 'SYNC_STATUS';
        $createdAt = new \DateTime('2019-01-01 12:00:00');
        $report = $this->createMock(\common_report_Report::class);
        $report->expects($this->once())
            ->method('JsonSerialize')
            ->willReturn(['data' => 'REPORT_DATA']);

        $expectedInsertData = [
            'sync_id' => $syncId,
            'box_id' => $boxId,
            'organization_id' => $orgId,
            'data' => '{"ENTITY_TYPE":{"ACTION":"AMOUNT"}}',
            'status' => $status,
            'report' => '{"data":"REPORT_DATA"}',
            'created_at' => '2019-01-01 12:00:00',
        ];

        $this->persistenceMock->expects($this->once())
            ->method('insert')
            ->with($tableName, $expectedInsertData);

        $entity = new SyncLogEntity($syncId, $boxId, $orgId, $data, $status, $report, $createdAt);
        $this->object->create($entity);
    }
}

