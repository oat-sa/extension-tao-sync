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
use oat\taoSync\model\SyncLog\SyncLogFilter;
use oat\taoSync\scripts\install\RegisterRdsSyncLogStorage;

/**
 * Class RdsSyncLogStorageTest
 */
class RdsSyncLogStorageTest extends TestCase
{
    const SYNC_ID = '111';
    const BOX_ID = 'BOX_ID';
    const ORGANIZATION_ID = 'TEST_ORGANIZATION_ID';
    const STATUS = 'TEST_STATUS';
    const CREATED_AT = '2019-01-01 12:00:00';
    const FINISHED_AT = '2019-01-02 12:00:00';

    /**
     * @var RdsSyncLogStorage
     */
    private $object;

    /**
     * @var \common_report_Report|\PHPUnit_Framework_MockObject_MockObject
     */
    private $reportMock;

    private $logData = ['ENTITY_TYPE' => ['ACTION' => 'AMOUNT']];

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->reportMock = $this->createMock(\common_report_Report::class);
        $this->reportMock->method('JsonSerialize')
            ->willReturn(['data' => 'REPORT_DATA']);
        $sqlMock = $this->getSqlMock('sync_log_storage_test');
        $persistence = $sqlMock->getPersistenceById('sync_log_storage_test');

        $registerRdsSyncLog = new RegisterRdsSyncLogStorage();
        $registerRdsSyncLog->createTable($persistence);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            'generis/persistences' => $sqlMock,
        ]);

        $this->object = new RdsSyncLogStorage([
            RdsSyncLogStorage::OPTION_PERSISTENCE_ID => 'sync_log_storage_test'
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
        $entity = $this->getEntity();

        $filter = new SyncLogFilter();

        $totalCountBefore = $this->object->count($filter);
        $this->assertEquals(0, $totalCountBefore, 'There should not be any records before insert.');

        $this->object->create($entity);

        $totalCountAfter = $this->object->count($filter);
        $this->assertEquals(1, $totalCountAfter, 'There must be only one record after insert.');
    }

    /**
     * Test getById method.
     */
    public function testGetByIdNoRecord()
    {
        $this->expectException(\common_exception_NotFound::class);

        $this->object->getById(1);
    }


    /**
     * Test getById method.
     */
    public function testGetById()
    {
        $entity = $this->getEntity();

        $id = $this->object->create($entity);
        $storedData = $this->object->getById($id);

        $expectedData = [
            'box_id' => self::BOX_ID,
            'sync_id' => self::SYNC_ID,
            'organization_id' => self::ORGANIZATION_ID,
            'status' => self::STATUS,
            'data' => '{"ENTITY_TYPE":{"ACTION":"AMOUNT"}}',
            'created_at' => self::CREATED_AT,
            'finished_at' => null,
        ];

        $this->assertArraySubset($expectedData, $storedData);
    }

    /**
     * Test getBySyncIdAndBoxId method.
     */
    public function testGetBySyncIdAndBoxIdNoRecord()
    {
        $this->expectException(\common_exception_NotFound::class);

        $this->object->getBySyncIdAndBoxId(self::SYNC_ID, self::BOX_ID);
    }


    /**
     * Test getBySyncIdAndBoxId method.
     */
    public function testGetBySyncIdAndBoxId()
    {
        $entity = $this->getEntity();

        $this->object->create($entity);
        $storedData = $this->object->getBySyncIdAndBoxId(self::SYNC_ID, self::BOX_ID);

        $expectedData = [
            'box_id' => self::BOX_ID,
            'sync_id' => self::SYNC_ID,
            'organization_id' => self::ORGANIZATION_ID,
            'status' => self::STATUS,
            'data' => '{"ENTITY_TYPE":{"ACTION":"AMOUNT"}}',
            'created_at' => self::CREATED_AT,
            'finished_at' => null,
        ];

        $this->assertArraySubset($expectedData, $storedData);
    }

    /**
     * Test count method.
     */
    public function testCount()
    {
        $entity1 = new SyncLogEntity(
            111,
            self::BOX_ID,
            self::ORGANIZATION_ID,
            $this->logData,
            self::STATUS,
            $this->reportMock,
            new \DateTime(self::CREATED_AT)
        );

        $entity2 = new SyncLogEntity(
            222,
            self::BOX_ID,
            self::ORGANIZATION_ID,
            $this->logData,
            self::STATUS,
            $this->reportMock,
            new \DateTime(self::CREATED_AT)
        );

        $entity3 = new SyncLogEntity(
            333,
            self::BOX_ID,
            self::ORGANIZATION_ID,
            $this->logData,
            self::STATUS,
            $this->reportMock,
            new \DateTime(self::CREATED_AT)
        );

        $filter = new SyncLogFilter();

        $totalCountBefore = $this->object->count($filter);
        $this->assertEquals(0, $totalCountBefore, 'There should not be any records before insert.');

        $this->object->create($entity1);
        $this->object->create($entity2);
        $this->object->create($entity3);

        $totalCountAfter = $this->object->count($filter);
        $this->assertEquals(3, $totalCountAfter, 'Total amount must be as expected.');
    }

    /**
     * Test search method.
     */
    public function testSearch()
    {
        $entity1 = new SyncLogEntity(
            111,
            self::BOX_ID,
            'orgId1',
            $this->logData,
            self::STATUS,
            $this->reportMock,
            new \DateTime(self::CREATED_AT)
        );

        $entity2 = new SyncLogEntity(
            222,
            self::BOX_ID,
            'orgId2',
            $this->logData,
            self::STATUS,
            $this->reportMock,
            new \DateTime(self::CREATED_AT)
        );

        $entity3 = new SyncLogEntity(
            333,
            self::BOX_ID,
            'organizationId3',
            $this->logData,
            self::STATUS,
            $this->reportMock,
            new \DateTime(self::CREATED_AT)
        );

        $filter = new SyncLogFilter();

        $totalCountBefore = $this->object->count($filter);
        $this->assertEquals(0, $totalCountBefore, 'There should not be any records before insert.');

        $this->object->create($entity1);
        $this->object->create($entity2);
        $this->object->create($entity3);

        $filter = new SyncLogFilter();
        $filter->eq('organization_id', 'orgId1');
        $result = $this->object->search($filter);
        $this->assertCount(1, $result);

        $filter = new SyncLogFilter();
        $filter->neq('organization_id', 'orgId1');
        $result = $this->object->search($filter);
        $this->assertCount(2, $result);

        $filter = new SyncLogFilter();
        $filter->lt('sync_id', 222);
        $result = $this->object->search($filter);
        $this->assertCount(1, $result);

        $filter = new SyncLogFilter();
        $filter->lte('sync_id', 222);
        $result = $this->object->search($filter);
        $this->assertCount(2, $result);

        $filter = new SyncLogFilter();
        $filter->gt('sync_id', 222);
        $result = $this->object->search($filter);
        $this->assertCount(1, $result);

        $filter = new SyncLogFilter();
        $filter->lte('sync_id', 222);
        $result = $this->object->search($filter);
        $this->assertCount(2, $result);

        $filter = new SyncLogFilter();
        $filter->notIn('sync_id', ['111', '222']);
        $result = $this->object->search($filter);
        $this->assertCount(1, $result);

        $filter = new SyncLogFilter();
        $filter->in('sync_id', [111, 222]);
        $result = $this->object->search($filter);
        $this->assertCount(2, $result);

        $filter = new SyncLogFilter();
        $filter->like('organization_id', 'orgId');
        $result = $this->object->search($filter);
        $this->assertCount(2, $result);

        $filter = new SyncLogFilter();
        $filter->notLike('organization_id', 'orgId');
        $result = $this->object->search($filter);
        $this->assertCount(1, $result);
    }

    /**
     * Test update method.
     */
    public function testUpdate()
    {
        $entity = $this->getEntity();

        $id = $this->object->create($entity);

        $storedEntity = $this->getEntity($id);
        $storedEntity->setData(['NEW DATA' => 'NEW VALUE']);
        $storedEntity->setFinishTime(new \DateTime(self::FINISHED_AT));

        $this->object->update($storedEntity);
        $storedData = $this->object->getById($id);

        $expectedData = [
            'box_id' => self::BOX_ID,
            'sync_id' => self::SYNC_ID,
            'organization_id' => self::ORGANIZATION_ID,
            'data' => '{"NEW DATA":"NEW VALUE"}',
            'finished_at' => self::FINISHED_AT,
        ];

        $this->assertArraySubset($expectedData, $storedData, 'Returned log record data must be updated.');
    }

    /**
     * @param integer|null $id
     * @return SyncLogEntity
     */
    private function getEntity($id = null)
    {

        return new SyncLogEntity(
            self::SYNC_ID,
            self::BOX_ID,
            self::ORGANIZATION_ID,
            $this->logData,
            self::STATUS,
            $this->reportMock,
            new \DateTime(self::CREATED_AT),
            $id
        );
    }
}

