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

namespace oat\taoSync\test\unit\Execution;

use common_exception_NotFound;
use oat\generis\test\TestCase;
use oat\taoDelivery\model\execution\ServiceProxy;
use oat\taoItems\model\preview\OntologyItemNotFoundException;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoProctoring\model\implementation\DeliveryExecutionStateService;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringData;
use oat\taoProctoring\model\monitorCache\DeliveryMonitoringService;
use oat\taoSync\model\Execution\DeliveryExecutionStatusManager;
use Zend\ServiceManager\ServiceLocatorInterface;

class DeliveryExecutionStatusManagerTest extends TestCase
{
    /**
     * @var ServiceLocatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serviceLocatorMock;

    /**
     * @var DeliveryMonitoringService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deliveryMonitoringMock;

    /**
     * @var ServiceProxy|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serviceProxyMock;

    /**
     * @var DeliveryExecutionStateService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deliveryExecutionStateMock;

    /**
     * @var DeliveryExecutionStatusManager
     */
    private $object;

    protected function setUp()
    {
        parent::setUp();

        $this->deliveryMonitoringMock = $this->createMock(DeliveryMonitoringService::class);
        $this->serviceProxyMock = $this->createMock(ServiceProxy::class);
        $this->deliveryExecutionStateMock = $this->createMock(DeliveryExecutionStateService::class);

        $this->serviceLocatorMock = $this->getServiceLocatorMock([
            DeliveryMonitoringService::SERVICE_ID       => $this->deliveryMonitoringMock,
            ServiceProxy::SERVICE_ID                    => $this->serviceProxyMock,
            DeliveryExecutionStateService::SERVICE_ID   => $this->deliveryExecutionStateMock,
        ]);

        $this->object = new DeliveryExecutionStatusManager();
        $this->object->setServiceLocator($this->serviceLocatorMock);
    }

    /**
     * Test that getExecutionsInProgress returns list of DeliveryMonitoringData objects
     */
    public function testGetExecutionsInProgressReturnsArrayOfObjects()
    {
        $monitoringDataMock = $this->createMock(DeliveryMonitoringData::class);

        $this->deliveryMonitoringMock
            ->method('find')
            ->willReturn([$monitoringDataMock]);

        $result = $this->object->getExecutionsInProgress();

        $this->assertInternalType('array', $result, 'Method must return array.');
        $this->assertInstanceOf(DeliveryMonitoringData::class, $result[0], 'Method must return list of DeliveryMonitoringData objects.');
    }

    /**
     * Test that terminateDeliveryExecutions throws OntologyItemNotFoundException
     * when delivery execution with given id does not exist
     */
    public function testTerminateDeliveryExecutionsThrowsNotFoundException()
    {
        $this->expectException(OntologyItemNotFoundException::class);
        $executionIds = ['EXECUTION_ID'];

        $this->serviceProxyMock
            ->method('getDeliveryExecution')
            ->willThrowException(new common_exception_NotFound(''));

        $this->object->terminateDeliveryExecutions($executionIds);
    }

    /**
     * Test that service successfully terminates delivery executions by ids
     */
    public function testTerminateDeliveryExecutions()
    {
        $executionIds = ['EXECUTION_ID'];
        $executionMock = $this->createMock(DeliveryExecution::class);
        $this->serviceProxyMock
            ->method('getDeliveryExecution')
            ->willReturn($executionMock);

        $this->deliveryExecutionStateMock
            ->method('terminateExecution')
            ->willReturn(true);

        $result = $this->object->terminateDeliveryExecutions($executionIds);

        $this->assertTrue($result, 'Method must return expected value.');
    }
}

