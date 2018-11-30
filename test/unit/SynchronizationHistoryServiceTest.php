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
use oat\taoSync\model\SynchronizationHistory\SynchronizationHistoryService;
use oat\generis\test\TestCase;

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
     * @var TaskLogInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $taskLogMock;

    /**
     * @var User|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formatterMock = $this->createMock(HistoryPayloadFormatter::class);
        $this->taskLogMock = $this->createMock(TaskLogInterface::class);
        $this->userMock = $this->createMock(User::class);
        $this->object = new SynchronizationHistoryService($this->formatterMock);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            TaskLogInterface::SERVICE_ID => $this->taskLogMock
        ]);
        $this->object->setServiceLocator($serviceLocatorMock);
    }

    /**
     * Test getSyncHistory method
     */
    public function testGetSyncHistory()
    {
        $expectedPayload = ['Payload results array'];
        $this->userMock->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('ID');

        $datatableRequestMock = $this->createMock(DatatableRequest::class);
        $payloadMock = $this->createMock(DataTablePayload::class);
        $payloadMock->expects($this->once())
            ->method('getPayload')
            ->willReturn($expectedPayload);

        $this->taskLogMock->expects($this->once())
            ->method('getDataTablePayload')
            ->willReturn($payloadMock);

        $result = $this->object->getSyncHistory($this->userMock, $datatableRequestMock);

        $this->assertEquals($expectedPayload, $result, 'Synchronization history payload must be as expected.');
    }

    /**
     * Test getSyncReport method
     */
    public function testGetSyncReport()
    {
        $id = 'ID';
        $userId = 'UserID';
        $expectedReport = $this->createMock(\common_report_Report::class);
        $this->userMock->expects($this->once())
            ->method('getIdentifier')
            ->willReturn($userId);

        $taskLogEntityMock = $this->createMock(TaskLogEntity::class);
        $taskLogEntityMock->expects($this->once())
            ->method('getReport')
            ->willReturn($expectedReport);

        $this->taskLogMock->expects($this->once())
            ->method('getByIdAndUser')
            ->with($id, $userId)
            ->willReturn($taskLogEntityMock);

        $result = $this->object->getSyncReport($this->userMock, $id);

        $this->assertEquals($expectedReport, $result, 'Returned report must be as expected.');
    }
}

