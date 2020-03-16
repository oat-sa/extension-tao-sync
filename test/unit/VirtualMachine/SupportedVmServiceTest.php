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

use core_kernel_classes_Class;
use core_kernel_classes_Literal;
use core_kernel_classes_Property;
use oat\generis\model\data\Ontology;
use oat\generis\test\TestCase;
use oat\taoSync\model\VirtualMachine\SupportedVmService;
use oat\generis\test\MockObject;

class SupportedVmServiceTest extends TestCase
{
    /**
     * @var SupportedVmService
     */
    private $object;

    /**
     * @var Ontology|MockObject
     */
    private $modelMock;

    /**
     * @var core_kernel_classes_Class|MockObject
     */
    private $rootClassMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootClassMock = $this->createMock(core_kernel_classes_Class::class);
        $this->modelMock = $this->createMock(Ontology::class);
        $this->modelMock
            ->method('getClass')
            ->willReturn($this->rootClassMock);

        $this->object = new SupportedVmService();
        $this->object->setModel($this->modelMock);
    }

    public function testGetRootClassReturnsCorrectObject()
    {
        $result = $this->object->getRootClass();
        $this->assertInstanceOf(core_kernel_classes_Class::class, $result, 'Method must return instance of correct class.');
    }

    /**
     * @param array $propertyValues
     * @param array $expectedResult
     *
     * @dataProvider dataProviderTestGetSupportedVmVersions
     */
    public function testGetSupportedVmVersions(array $propertyValues, array $expectedResult)
    {
        $propertyMock = $this->createMock(core_kernel_classes_Property::class);
        $this->modelMock->method('getProperty')
            ->willReturn($propertyMock);

        $this->rootClassMock->method('getInstancesPropertyValues')
            ->willReturn($propertyValues);

        $result = $this->object->getSupportedVmVersions();
        $this->assertEquals($expectedResult, $result, 'List of supported TAO VM versions must be as expected.');
    }

    public function dataProviderTestGetSupportedVmVersions()
    {
        return [
            'Empty list' => [
                'propertyValues' => [],
                'expectedResult' => [],
            ],
            'Supported versions' => [
                'propertyValues' => [
                    new core_kernel_classes_Literal('VERSION_1'),
                    new core_kernel_classes_Literal('VERSION_2'),
                ],
                'expectedResult' => [
                    'VERSION_1',
                    'VERSION_2',
                ]
            ]
        ];
    }
}
