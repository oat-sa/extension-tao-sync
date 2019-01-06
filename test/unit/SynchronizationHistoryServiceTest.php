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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\test\unit;

use oat\oatbox\user\User;
use oat\tao\model\datatable\DatatableRequest;
use oat\tao\model\taskQueue\TaskLog\DataTablePayload;
use oat\tao\model\taskQueue\TaskLog\Entity\TaskLogEntity;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoSync\model\SynchronizationHistory\HistoryPayloadFormatter;
use oat\taoSync\model\SynchronizationHistory\HistoryPayloadFormatterInterface;
use oat\taoSync\model\SynchronizationHistory\SynchronizationHistoryService;
use oat\generis\test\TestCase;
use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

class SynchronizationHistoryServiceTest extends TestCase
{
    /**
     * @var SynchronizationHistoryService
     */
    private $object;

    /**
     * @var HistoryPayloadFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formatterMock;

    /**
     * @var SyncLogServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncLogServiceMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formatterMock = $this->createMock(HistoryPayloadFormatter::class);
        $this->syncLogServiceMock = $this->createMock(SyncLogServiceInterface::class);
        $this->object = new SynchronizationHistoryService([]);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            SyncLogServiceInterface::SERVICE_ID => $this->syncLogServiceMock,
            HistoryPayloadFormatterInterface::SERVICE_ID => $this->formatterMock,
        ]);
        $this->object->setServiceLocator($serviceLocatorMock);
    }

    /**
     * Test getSyncHistory method
     */
    public function testGetSyncHistory()
    {
        $rows = 10;
        $page = 1;
        $totalCount = 49;
        $syncLogRecords = [
            0 => ['SYNC LOG RECORDS']
        ];

        $expectedResult = [
            'rows'    => $rows,
            'page'    => $page,
            'amount'  => 1,
            'total'   => 5,
            'data'    => $syncLogRecords,
        ];

        $requestMock = $this->createMock(DatatableRequest::class);
        $requestMock->expects($this->once())
            ->method('getFilters')
            ->willReturn([]);
        $requestMock->expects($this->once())
            ->method('getPage')
            ->willReturn($page);
        $requestMock->expects($this->once())
            ->method('getRows')
            ->willReturn(10);

        $this->syncLogServiceMock->expects($this->once())
            ->method('count')
            ->willReturn($totalCount);
        $this->syncLogServiceMock->expects($this->once())
            ->method('search')
            ->willReturn($syncLogRecords);

        $this->formatterMock->expects($this->once())
            ->method('format')
            ->willReturn($syncLogRecords[0]);

        $userMock = $this->createMock(User::class);

        $result = $this->object->getSyncHistory($userMock, $requestMock);

        $this->assertEquals($expectedResult, $result, 'Synchronization history payload must be as expected.');
    }

    /**
     * Test getSyncReport method
     */
    public function testGetSyncReport()
    {
        $id = 'ID';
        $expectedReport = $this->createMock(\common_report_Report::class);
        $syncLogEntityMock = $this->createMock(SyncLogEntity::class);
        $syncLogEntityMock->expects($this->once())
            ->method('getReport')
            ->willReturn($expectedReport);

        $this->syncLogServiceMock->expects($this->once())
            ->method('getById')
            ->with($id)
            ->willReturn($syncLogEntityMock);

        $result = $this->object->getSyncReport($id);

        $this->assertEquals($expectedReport, $result, 'Returned report must be as expected.');
    }
}

