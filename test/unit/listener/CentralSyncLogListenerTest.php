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
use oat\taoSync\model\event\SyncRequestEvent;
use oat\taoSync\model\listener\CentralSyncLogListener;
use Psr\Log\LoggerInterface;

class CentralSyncLogListenerTest extends TestCase
{
    /**
     * @var CentralSyncLogListener
     */
    private $object;

    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new CentralSyncLogListener();
    }

    /**
     * Test that validation of synchronization event parameters fails
     * and LoggerInterface::error() method is called.
     *
     * @param array $params
     *
     * @dataProvider dataProviderValidateParametersFails
     */
    public function testValidateParametersFailsRequiredParamMissing(array $params)
    {
        $eventMock = $this->createMock(SyncRequestEvent::class);
        $eventMock->method('getSyncParameters')
            ->willReturn($params);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error');
        $this->object->setLogger($loggerMock);

        $this->object->logSyncRequest($eventMock);
    }

    public function dataProviderValidateParametersFails()
    {
        return [
            'sync_id is missing' => [
                'params' => [
                    'box_id' => 'TEST_BOX_ID',
                    'organisation_id' => 'TEST_ORGANISATION_ID',
                    'tao_version' => 'TEST_TAO_VERSION',
                ],
            ],
            'sync_id is empty' => [
                'params' => [
                    'sync_id' => '',
                    'box_id' => 'TEST_BOX_ID',
                    'organisation_id' => 'TEST_ORGANISATION_ID',
                    'tao_version' => 'TEST_TAO_VERSION',
                ],
            ],
            'box_id is missing' => [
                'params' => [
                    'sync_id' => 'TEST_SYNC_ID',
                    'organisation_id' => 'TEST_ORGANISATION_ID',
                    'tao_version' => 'TEST_TAO_VERSION',
                ],
            ],
            'box_id is empty' => [
                'params' => [
                    'sync_id' => 'TEST_SYNC_ID',
                    'box_id' => '',
                    'organisation_id' => 'TEST_ORGANISATION_ID',
                    'tao_version' => 'TEST_TAO_VERSION',
                ],
            ],
            'organisation_id is missing' => [
                'params' => [
                    'sync_id' => 'TEST_SYNC_ID',
                    'box_id' => 'TEST_BOX_ID',
                    'tao_version' => 'TEST_TAO_VERSION',
                ],
            ],
            'organisation_id is empty' => [
                'params' => [
                    'sync_id' => 'TEST_SYNC_ID',
                    'box_id' => 'TEST_BOX_ID',
                    'organisation_id' => '',
                    'tao_version' => 'TEST_TAO_VERSION',
                ],
            ],
            'tao_version is missing' => [
                'params' => [
                    'sync_id' => 'TEST_SYNC_ID',
                    'box_id' => 'TEST_BOX_ID',
                    'organisation_id' => 'TEST_ORGANISATION_ID',
                ],
            ],
            'tao_version is missing' => [
                'params' => [
                    'sync_id' => 'TEST_SYNC_ID',
                    'box_id' => 'TEST_BOX_ID',
                    'organisation_id' => 'TEST_ORGANISATION_ID',
                    'tao_version' => '',
                ],
            ],
        ];
    }
}
