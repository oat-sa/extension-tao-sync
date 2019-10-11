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

namespace oat\taoSync\test\unit\Validator;

use oat\generis\test\TestCase;
use oat\taoSync\model\Exception\SyncRequestFailedException;
use oat\taoSync\model\Validator\SyncParamsValidator;
use oat\taoSync\model\VirtualMachine\SupportedVmService;
use oat\generis\test\MockObject;

class SyncParamsValidatorTest extends TestCase
{
    /**
     * @var SyncParamsValidator
     */
    private $object;

    /**
     * @var SupportedVmService|MockObject
     */
    private $supportedVmServiceMock;

    private $supportedVersions = [
        'VERSION_1',
        'VERSION_2'
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->supportedVmServiceMock = $this->createMock(SupportedVmService::class);
        $this->supportedVmServiceMock->method('getSupportedVmVersions')
            ->willReturn($this->supportedVersions);

        $serviceLocatorMock = $this->getServiceLocatorMock([
            SupportedVmService::SERVICE_ID => $this->supportedVmServiceMock
        ]);

        $this->object = new SyncParamsValidator();
        $this->object->setServiceLocator($serviceLocatorMock);
    }

    public function testValidateFailsVmVersionMissing()
    {
        $this->expectException(SyncRequestFailedException::class);

        $syncParams = [];
        $this->object->validate($syncParams);
    }

    public function testValidateFailsVmVersionNotSupported()
    {
        $this->expectException(SyncRequestFailedException::class);

        $syncParams = [
            'tao_version' => 'NOT_SUPPORTED_TAO_VERSION'
        ];

        $this->object->validate($syncParams);
    }

    public function testValidateCorrectRequestData()
    {
        $syncParams = [
            'tao_version' => 'VERSION_1'
        ];

        $this->object->validate($syncParams);
    }
}

