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

namespace oat\taoSync\test\User;

use core_kernel_users_InvalidLoginException;
use oat\taoSync\model\User\HandShakeAuthAdapter;
use oat\generis\test\TestCase;

class HandShakeAuthAdapterTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testAuthenticateRemote()
    {
        /** @var HandShakeAuthAdapter $handShakeAdapter */
        $handShakeAdapter = $this->getMockBuilder(HandShakeAuthAdapter::class)->setMethods([
            'callParentAuthenticate','handShakeWithServer'
        ])->getMockForAbstractClass();

        $handShakeAdapter
            ->method('callParentAuthenticate')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new core_kernel_users_InvalidLoginException()),
                true
            ));

        $handShakeAdapter
            ->method('handShakeWithServer')
            ->willReturn(true);

        $this->assertTrue($handShakeAdapter->authenticate()) ;
    }


    /**
     * @expectedException \core_kernel_users_InvalidLoginException
     * @throws \Exception
     */
    public function testAuthenticateRemoteFailed()
    {
        /** @var HandShakeAuthAdapter $handShakeAdapter */
        $handShakeAdapter = $this->getMockBuilder(HandShakeAuthAdapter::class)->setMethods([
            'callParentAuthenticate', 'handShakeWithServer', 'logError'
        ])->getMockForAbstractClass();

        $handShakeAdapter
            ->method('callParentAuthenticate')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new core_kernel_users_InvalidLoginException()),
                true
            ));

        $handShakeAdapter
            ->method('handShakeWithServer')
            ->willReturn(false);

        $handShakeAdapter->authenticate();
    }

    /**
     * @throws \Exception
     */
    public function testAuthenticateLocally()
    {
        /** @var HandShakeAuthAdapter $handShakeAdapter */
        $handShakeAdapter = $this->getMockBuilder(HandShakeAuthAdapter::class)->setMethods([
            'callParentAuthenticate','handShakeWithServer', 'getResource', 'getProperty'
        ])->getMockForAbstractClass();

        $userMock = $this->getMockBuilder(\common_user_User::class)->setMethods([
            'getIdentifier'
        ])->getMockForAbstractClass();

        $resourceMock = $this->getMockBuilder(\core_kernel_classes_Resource::class)->setMethods([
            'getOnePropertyValue'
        ])->disableOriginalConstructor()->getMock();

        $propertyMock = $this->getMockBuilder(\core_kernel_classes_Property::class)
            ->disableOriginalConstructor()->getMock();

        $resourceMock->method('getOnePropertyValue')->willReturn(1);
        $userMock->method('getIdentifier')->willReturn('userId');
        $handShakeAdapter->method('getResource')->willReturn($resourceMock);
        $handShakeAdapter->method('getProperty')->willReturn($propertyMock);

        $handShakeAdapter
            ->method('callParentAuthenticate')
            ->will($this->onConsecutiveCalls($userMock));

        $handShakeAdapter->expects($this->once())->method('handShakeWithServer');

        $this->assertSame($userMock, $handShakeAdapter->authenticate()) ;
    }

    /**
     * @expectedException \core_kernel_users_InvalidLoginException
     * @throws \Exception
     */
    public function testAuthenticateLocallyFailed()
    {
        /** @var HandShakeAuthAdapter $handShakeAdapter */
        $handShakeAdapter = $this->getMockBuilder(HandShakeAuthAdapter::class)->setMethods([
            'callParentAuthenticate', 'handShakeWithServer', 'logError'
        ])->getMockForAbstractClass();

        $handShakeAdapter
            ->method('callParentAuthenticate')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new core_kernel_users_InvalidLoginException()),
                $this->throwException(new core_kernel_users_InvalidLoginException())
            ));

        $handShakeAdapter
            ->method('handShakeWithServer')
            ->will($this->onConsecutiveCalls(
                true
            ));

        $this->assertTrue($handShakeAdapter->authenticate()) ;
    }

}
