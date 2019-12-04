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

namespace oat\taoSync\test\unit\dataProvider;

use oat\generis\test\TestCase;
use oat\taoSync\model\dataProvider\AbstractDataProvider;
use oat\taoSync\model\dataProvider\SyncDataProviderCollection;
use oat\taoSync\model\Exception\SyncDataProviderException;

class SyncDataProviderCollectionTest extends TestCase
{
    public function testGetProvider()
    {
        $dataProvider = $this->createMock(AbstractDataProvider::class);
        $dataProvider->method('getType')->willReturn('test');
        $syncDataProviderCollection = new SyncDataProviderCollection(
            [
                SyncDataProviderCollection::OPTION_DATA_PROVIDERS => ['test' => $dataProvider]
            ]
        );
        $syncDataProviderCollection->setServiceLocator($this->getServiceLocatorMock());

        $this->assertEquals($dataProvider, $syncDataProviderCollection->getProvider('test'));
    }

    public function testGetInvalidProvider()
    {
        $syncDataProviderCollection = new SyncDataProviderCollection(
            [
                SyncDataProviderCollection::OPTION_DATA_PROVIDERS => [
                    'dp' => $this->createMock(AbstractDataProvider::class)
                ]
            ]
        );
        $syncDataProviderCollection->setServiceLocator($this->getServiceLocatorMock());
        $this->expectException(SyncDataProviderException::class);
        $syncDataProviderCollection->getProvider('test');
    }

    public function testGetDataTest()
    {
        $dataProvider1 = $this->getMockBuilder(AbstractDataProvider::class)
            ->setConstructorArgs(['key' => ['value1']])->getMock();

        $dataProvider1->method('getType')->willReturn('dp1');
        $dataProvider2 = $this->getMockBuilder(AbstractDataProvider::class)
            ->setConstructorArgs(['key' => ['value2']])->getMock();
        $dataProvider2->method('getType')->willReturn('dp2');
        $dataProvider3 = $this->getMockBuilder(AbstractDataProvider::class)
            ->setConstructorArgs(['key' => ['value3']])->getMock();
        $dataProvider3->method('getType')->willReturn('dp3');
        $dataProvider4 = $this->getMockBuilder(AbstractDataProvider::class)
            ->setConstructorArgs(['key' => ['value4']])->getMock();
        $dataProvider4->method('getType')->willReturn('dp4');
        $dataProvider4->method('getParent')->willReturn('dp3');
        $dataProvider5 = $this->getMockBuilder(AbstractDataProvider::class)
            ->setConstructorArgs(['key' => ['value5']])->getMock();
        $dataProvider5->method('getType')->willReturn('dp5');
        $dataProvider5->method('getParent')->willReturn('dp3');
        $dataProvider6 = $this->getMockBuilder(AbstractDataProvider::class)
            ->setConstructorArgs(['key' => ['value6']])->getMock();
        $dataProvider6->method('getType')->willReturn('dp6');
        $dataProvider6->method('getParent')->willReturn('dp5');

        $params = ['key' => 'value'];

        $dataProvider1->expects($this->once())
            ->method('getData')
            ->with($params)
            ->willReturn(['result1'=> 'data1']);

        $dataProvider2->expects($this->once())
            ->method('getData')
            ->with($params)
            ->willReturn(['result2'=> 'data2']);

        $dataProvider3->expects($this->once())
            ->method('getData')
            ->with($params)
            ->willReturn(['result3'=> 'data3']);

        $dataProvider4->expects($this->once())
            ->method('getData')
            ->with(['dp3' => ['result3'=> 'data3'], 'key' => 'value'])
            ->willReturn(['result4'=> 'data4']);

        $dataProvider5->expects($this->once())
            ->method('getData')
            ->with(['dp3' => ['result3'=> 'data3'], 'key' => 'value'])
            ->willReturn(['result5'=> 'data5']);

        $dataProvider6->expects($this->once())
            ->method('getData')
            ->with(['dp3' => ['result3'=> 'data3'], 'key' => 'value', 'dp5' => ['result5'=> 'data5']])
            ->willReturn(['result6'=> 'data6']);


        $syncDataProviderCollection = new SyncDataProviderCollection(
            [
                SyncDataProviderCollection::OPTION_DATA_PROVIDERS =>
                    [
                        'dp1' => $dataProvider1,
                        'dp2' => $dataProvider2,
                        'dp3' => $dataProvider3,
                        'dp4' => $dataProvider4,
                        'dp5' => $dataProvider5,
                        'dp6' => $dataProvider6
                    ]
            ]
        );
        $syncDataProviderCollection->setServiceLocator($this->getServiceLocatorMock());

        $this->assertEquals(
            [
                'dp1' => ['result1'=> 'data1'],
                'dp2' => ['result2'=> 'data2'],
                'dp3' => ['result3'=> 'data3'],
                'dp4' => ['result4'=> 'data4'],
                'dp5' => ['result5'=> 'data5'],
                'dp6' => ['result6'=> 'data6']
            ],
            $syncDataProviderCollection->getData($params)
        );
    }
}
