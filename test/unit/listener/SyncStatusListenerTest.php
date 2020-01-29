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

namespace oat\taoSync\test\unit\SynchronizationHistory;

use oat\generis\test\TestCase;
use common_report_Report as Report;
use oat\oatbox\log\LoggerService;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\event\SyncFailedEvent;
use oat\taoSync\model\listener\SyncStatusListener;
use Psr\Log\LoggerInterface;
use oat\generis\test\MockObject;

class SyncStatusListenerTest extends TestCase
{
    /**
     * @var SyncStatusListener
     */
    private $object;

    /**
     * @var SynchronisationClient|MockObject
     */
    private $syncClientMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    protected function setUp()
    {
        parent::setUp();

        $this->loggerMock = $this->createMock(LoggerService::class);
        $this->syncClientMock = $this->createMock(SynchronisationClient::class);
        $slMock = $this->getServiceLocatorMock([
            SynchronisationClient::SERVICE_ID => $this->syncClientMock,
            LoggerService::SERVICE_ID => $this->loggerMock,
        ]);

        $this->object = new SyncStatusListener([]);
        $this->object->setServiceLocator($slMock);
    }

    public function testSendSyncFailedConfirmationFailed()
    {
        $event = new SyncFailedEvent([], new Report(Report::TYPE_ERROR));
        $event->setReason('TEST FAILED');

        $this->syncClientMock->expects($this->once())
            ->method('sendSyncFailedConfirmation')
            ->willThrowException(new \common_Exception('Confirmation sending failed'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Confirmation sending failed', []);

        $this->object->sendSyncFailedConfirmation($event);
    }

    public function testSendSyncFailedConfirmationWithFailureReason()
    {
        $expectedResponse = ['SUCCESS_RESPONSE'];
        $syncParams = [];
        $failureReason = 'TEST CONFIRMATION FAILED';

        $report = new Report(Report::TYPE_INFO);
        $event = new SyncFailedEvent($syncParams, $report);
        $event->setReason($failureReason);

        $this->syncClientMock->expects($this->once())
            ->method('sendSyncFailedConfirmation')
            ->with($syncParams, $failureReason)
            ->willReturn($expectedResponse);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('["SUCCESS_RESPONSE"]');

        $this->object->sendSyncFailedConfirmation($event);
    }

    public function testSendSyncFailedConfirmationEmptyFailureReason()
    {
        $expectedResponse = ['SUCCESS_RESPONSE'];
        $errMsg1 = 'REPORT ERROR MESSAGE 1';
        $errMsg2 = 'REPORT ERROR MESSAGE 2';
        $syncParams = [];

        $report = new Report(Report::TYPE_INFO);
        $errReport1 = Report::createFailure($errMsg1);
        $errReport2 = Report::createFailure($errMsg2);
        $report->add($errReport1);
        $report->add($errReport2);

        $event = new SyncFailedEvent($syncParams, $report);
        $event->setReason('');

        $errors = [$errReport1, $errReport2];
        $this->syncClientMock->expects($this->once())
            ->method('sendSyncFailedConfirmation')
            ->with($syncParams, $errors)
            ->willReturn($expectedResponse);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('["SUCCESS_RESPONSE"]');

        $this->object->sendSyncFailedConfirmation($event);
    }
}
