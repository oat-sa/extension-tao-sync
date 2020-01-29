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

namespace oat\taoSync\test\unit\synchronisation;

use common_Exception;
use common_report_Report as Report;
use common_report_Report;
use oat\generis\test\TestCase;
use oat\oatbox\action\Action;
use oat\oatbox\event\EventManager;
use oat\tao\model\service\ApplicationService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\event\SyncStartedEvent;
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\VirtualMachine\VmVersionChecker;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeAll;
use oat\generis\test\MockObject;

class SynchronizeAllTest extends TestCase implements Action
{
    /**
     * @var EventManager|MockObject
     */
    private $eventManagerMock;

    /**
     * @var ApplicationService|MockObject
     */
    private $applicationServiceMock;

    /**
     * @var DataSyncHistoryService|MockObject
     */
    private $dataSyncHistoryServiceMock;

    /**
     * @var PublishingService|MockObject
     */
    private $publishingServiceMock;

    /**
     * @var VmVersionChecker|MockObject
     */
    private $vmVersionCheckerMock;

    /**
     * @var SynchronizeAll
     */
    private $object;

    /**
     * @var common_report_Report
     */
    private static $report;

    protected function setUp()
    {
        parent::setUp();

        $this->eventManagerMock = $this->getMockBuilder(EventManager::class)
            ->setMethods(['trigger'])
            ->getMock();

        $this->applicationServiceMock = $this->getMockBuilder(ApplicationService::class)
            ->setMethods(['getPlatformVersion'])
            ->getMock();

        $this->dataSyncHistoryServiceMock = $this->getMockBuilder(DataSyncHistoryService::class)
            ->setMethods(['createSynchronisation'])
            ->getMock();

        $this->publishingServiceMock = $this->getMockBuilder(PublishingService::class)
            ->setMethods(['getBoxIdByAction'])
            ->getMock();

        $this->vmVersionCheckerMock = $this->getMockBuilder(VmVersionChecker::class)
            ->setMethods(['isVmSupported'])
            ->getMock();

        $this->object = (new SynchronizeAll())->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    EventManager::SERVICE_ID => $this->eventManagerMock,
                    ApplicationService::SERVICE_ID => $this->applicationServiceMock,
                    DataSyncHistoryService::SERVICE_ID => $this->dataSyncHistoryServiceMock,
                    PublishingService::SERVICE_ID => $this->publishingServiceMock,
                    VmVersionChecker::SERVICE_ID => $this->vmVersionCheckerMock,
                ]
            )
        );
    }

    /**
     * @param array $params
     * @return common_report_Report
     */
    public function __invoke($params)
    {
        return self::$report;
    }

    /**
     * @param common_report_Report $report
     * @param string $eventClass
     * @dataProvider synchronizeAllDataProvider
     * @throws common_Exception
     */
    public function testSynchronizeAll(common_report_Report $report, $eventClass)
    {
        $params = ['paramKey' => 'paramValue'];

        $this->dataSyncHistoryServiceMock->expects($this->once())
            ->method('createSynchronisation')
            ->with($params)
            ->willReturn('syncId');

        $this->publishingServiceMock->expects($this->once())
            ->method('getBoxIdByAction')
            ->willReturn('boxId');


        $this->applicationServiceMock->expects($this->once())
            ->method('getPlatformVersion')
            ->willReturn('platformVersion');

        $this->vmVersionCheckerMock->expects($this->once())
            ->method('isVmSupported')
            ->with('platformVersion')
            ->willReturn(true);

        self::$report = $report;

        $finishReport = Report::createInfo('Synchronizing data');
        $finishReport->add(self::$report);
        if ($eventClass == SyncFailedEvent::class) {
            $finishReport->add(Report::createFailure('An unexpected PHP error has occurred.'));
        }
        $expectedParams = [
            'paramKey' => 'paramValue',
            'sync_id' => 'syncId',
            'box_id' => 'boxId',
            'tao_version' => 'platformVersion'
        ];

        $this->eventManagerMock->expects($this->exactly(2))
            ->method('trigger')
            ->withConsecutive(
                [$this->isInstanceOf(SyncStartedEvent::class)],
                [new $eventClass($expectedParams, $finishReport)]
            );

        $object = $this->object;
        $object(array_merge($params, ['actionsToRun' => [self::class]]));
    }

    /**
     * @return array
     */
    public function synchronizeAllDataProvider()
    {
        return [
            [Report::createSuccess(''), SyncFinishedEvent::class],
            [Report::createFailure(''), SyncFailedEvent::class]
        ];
    }
}
