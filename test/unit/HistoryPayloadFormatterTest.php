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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\test\unit;

use oat\tao\model\taskQueue\TaskLog\CategorizedStatus;
use oat\tao\model\taskQueue\TaskLog\Entity\TaskLogEntity;
use oat\taoSync\model\SynchronizationHistory\HistoryPayloadFormatter;
use oat\generis\test\TestCase;


/**
 * Class HistoryPayloadFormatterTest
 */
class HistoryPayloadFormatterTest extends TestCase
{
    /**
     * @var HistoryPayloadFormatter
     */
    private $object;

    protected function setUp()
    {
        parent::setUp();
        $this->object = new HistoryPayloadFormatter();
    }

    /**
     * @param $id
     * @param $status
     * @param $createdAt
     * @param $params
     * @param $reportMessage
     * @param $expected
     *
     * @dataProvider dataProviderTestFormat
     */
    public function testFormat($id, $status, $createdAt, $params, $reportMessage, $expected)
    {
        $statusMock = $this->createMock(CategorizedStatus::class);
        $statusMock->expects($this->once())
            ->method('getLabel')
            ->willReturn($status);

        $reportMock = $this->createMock(\common_report_Report::class);
        $reportMock->expects($this->once())
            ->method('hasChildren')
            ->willReturn(false);
        $reportMock->expects($this->once())
            ->method('getMessage')
            ->willReturn($reportMessage);

        $logMock = $this->createMock(TaskLogEntity::class);
        $logMock->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $logMock->expects($this->once())
            ->method('getStatus')
            ->willReturn($statusMock);
        $time = new \DateTime($createdAt);
        $logMock->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($time);
        $logMock->expects($this->once())
            ->method('getParameters')
            ->willReturn($params);
        $logMock->expects($this->once())
            ->method('getReport')
            ->willReturn($reportMock);

        $formattedRow = $this->object->format($logMock);

        $this->assertEquals($expected, $formattedRow, 'Formatted payload row must be as expected');
    }

    /**
     * @return array
     */
    public function dataProviderTestFormat()
    {
        return [
            'Successful' => [
                'id' => 'ID1',
                'status' => 'Completed',
                'created_at' => '26-11-2018 13:12',
                'parameters' => [
                    'organisation_id' => 'orgId1',
                ],
                'reportMessage' => '3 delivery execution exports have been acknowledged.',
                'expected' => [
                    'id' => 'ID1',
                    'status' => 'Completed',
                    'created_at' => '26/11/2018 01:12 pm',
                    'organisation' => 'orgId1',
                    'data' => '3 delivery execution synchronized',
                ],
            ],
            'Failed' => [
                'id' => 'ID1',
                'status' => 'Failed',
                'created_at' => '26-11-2018 03:12',
                'parameters' => [
                    'organisation_id' => 'orgId1',
                ],
                'reportMessage' => '(delivery) 1 entities created.',
                'expected' => [
                    'id' => 'ID1',
                    'status' => 'Failed',
                    'created_at' => '26/11/2018 03:12 am',
                    'organisation' => 'orgId1',
                    'data' => '(delivery) 1 entities created',
                ],
            ],
        ];
    }
}

