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
use oat\generis\model\data\Ontology;
use oat\taoSync\model\formatter\SynchronizerFormatter;
use oat\taoSync\model\server\HandShakeServerResponse;
use oat\generis\test\TestCase;
use oat\generis\test\MockObject;

class HandShakeServerResponseTest extends TestCase
{
    public function testAsArray()
    {
        $handShakeResponse = new HandShakeServerResponse($this->mockResource(), $this->mockResource(), $this->mockSynchronizer());
        $handShakeResponse->setModel($this->mockModel());

        $this->assertEquals([
            'syncUser' => [
                'property1' => 'value1'
            ],
            'oauthInfo' => [
                'key' => 'key',
                'secret' => 'secret',
                'tokenUrl' => 'tokenUrl',
            ]
        ], $handShakeResponse->asArray());
    }

    /**
     * @return MockObject
     */
    protected function mockResource()
    {
        $literalMockKey = $this->getMockBuilder(\core_kernel_classes_Literal::class);
        $literalMockKey->literal = 'key';

        $literalMockSecret = $this->getMockBuilder(\core_kernel_classes_Literal::class);
        $literalMockSecret->literal = 'secret';

        $literalMockToken = $this->getMockBuilder(\core_kernel_classes_Literal::class);
        $literalMockToken->literal = 'tokenUrl';

        $mock = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setMethods(['getUniquePropertyValue'])
            ->disableOriginalConstructor()->getMock();

        $mock
            ->method('getUniquePropertyValue')
            ->will(
                $this->onConsecutiveCalls(
                    $literalMockKey,
                    $literalMockSecret,
                    $literalMockToken
                )
            );

        return $mock;
    }

    protected function mockSynchronizer()
    {
        $mock = $this->getMockBuilder(SynchronizerFormatter::class)->disableOriginalConstructor()->getMock();
        $mock
            ->method('format')
            ->willReturn([
                'property1' => 'value1'
            ]);

        return $mock;
    }

    /**
     * @return MockObject
     */
    protected function mockModel()
    {
        $mock = $this->getMockBuilder(Ontology::class)->disableOriginalConstructor()->getMock();
        $mock
            ->method('getProperty')
            ->willReturn(new \core_kernel_classes_Property('PROP'));

        return $mock;
    }
}
