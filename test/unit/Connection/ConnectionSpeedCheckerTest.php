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

namespace oat\taoSync\test\unit\Connection;

use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\Connection\ConnectionSpeedChecker;
use oat\generis\test\TestCase;

class ConnectionSpeedCheckerTest extends TestCase
{
    /**
     * Test that connection checker calls remote environment via sync client.
     *
     * @throws \common_Exception
     */
    public function testRun()
    {
        $clientMock = $this->createMock(SynchronisationClient::class);
        $clientMock->expects($this->exactly(2))
            ->method('callUrl');

        $serviceLocatorMock = $this->getServiceLocatorMock([
            SynchronisationClient::SERVICE_ID => $clientMock
        ]);

        $object = new ConnectionSpeedChecker();
        $object->setServiceLocator($serviceLocatorMock);

        $object->run();
    }
}

