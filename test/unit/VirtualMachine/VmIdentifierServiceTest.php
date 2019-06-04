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

namespace oat\taoSync\test\unit\VirtualMachine;

use oat\generis\test\TestCase;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\VirtualMachine\VmIdentifierService;

class VmIdentifierServiceTest extends TestCase
{
    /**
     * @var VmIdentifierService
     */
    private $object;

    /**
     * @var PublishingService|\PHPUnit_Framework_MockObject_MockObject
     */
    private $publishingServiceMock;

    protected function setUp()
    {
        parent::setUp();

        $this->publishingServiceMock = $this->createMock(PublishingService::class);
        $slMock  = $this->getServiceLocatorMock([
            PublishingService::SERVICE_ID => $this->publishingServiceMock
        ]);

        $this->object = new VmIdentifierService([]);
        $this->object->setServiceLocator($slMock);
    }

    public function testGetBoxIdFails()
    {
        $this->publishingServiceMock
            ->method('getBoxIdByAction')
            ->willThrowException(new \common_Exception('Dummy message'));

        $boxId = $this->object->getBoxId();
        $this->assertNull($boxId, 'Returned Box ID value must be as expected in case when Exception was thrown.');
    }

    public function testGetBoxId()
    {
        $expectedBoxId = 'DUMMY_BOX_ID';

        $this->publishingServiceMock
            ->method('getBoxIdByAction')
            ->willReturn($expectedBoxId);

        $boxId = $this->object->getBoxId();
        $this->assertEquals($expectedBoxId, $boxId, 'Returned Box ID value must be as expected.');
    }
}
