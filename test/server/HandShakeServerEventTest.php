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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\test\server;

use core_kernel_classes_Resource;
use oat\taoSync\model\server\HandShakeServerEvent;
use oat\generis\test\TestCase;
use oat\generis\test\MockObject;

class HandShakeServerEventTest extends TestCase
{
    public function testEvent()
    {
        $resource = $this->mockResource();
        $event = new HandShakeServerEvent($resource);

        $this->assertSame($resource, $event->getUserResource());
        $this->assertSame(HandShakeServerEvent::class, $event->getName());
    }

    /**
     * @return MockObject
     */
    protected function mockResource()
    {
        $mock = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setMethods(['getUniquePropertyValue'])
            ->disableOriginalConstructor()->getMock();

        return $mock;
    }
}
