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
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\VirtualMachine\VmVersionChecker;

class VmVersionCheckerTest extends TestCase
{
    /**
     * @param string $currentVmVersion
     * @param array $supportedVersions
     * @param boolean $expectedResult
     *
     * @dataProvider dataProviderTestIsVmSupported
     */
    public function testIsVmSupported($currentVmVersion, array $supportedVersions, $expectedResult)
    {
        $syncClientMock = $this->createMock(SynchronisationClient::class);
        $syncClientMock->method('getSupportedVmVersions')
            ->willReturn($supportedVersions);
        $serviceLocatorMock = $this->getServiceLocatorMock([
            SynchronisationClient::SERVICE_ID => $syncClientMock
        ]);
        $checker = new VmVersionChecker();
        $checker->setServiceLocator($serviceLocatorMock);

        $result = $checker->isVmSupported($currentVmVersion);

        $this->assertEquals($expectedResult, $result, 'Result of checking if VM version is supported must be as expected.');
    }

    /**
     * @return array
     */
    public function dataProviderTestIsVmSupported()
    {
        return [
            'VM is not supported' => [
                'currentVmVersion' => 'NOT_SUPPORTED_VERSION',
                'supportedVersions' => [
                    'VERSION_1',
                    'VERSION_2',
                ],
                'expectedResult' => false,
            ],
            'VM is supported' => [
                'currentVmVersion' => 'VERSION_1',
                'supportedVersions' => [
                    'VERSION_1',
                    'VERSION_2',
                ],
                'expectedResult' => true,
            ]
        ];
    }
}
