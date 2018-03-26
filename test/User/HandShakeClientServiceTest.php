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

use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use GuzzleHttp\Client;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\User\HandShakeClientRequest;
use oat\taoSync\model\User\HandShakeClientService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class HandShakeClientServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataSuccessProvider
     */
    public function testWithSuccess($data)
    {
        $service = $this->getService($data);
        $this->assertTrue($service->execute($this->getRequest()));
    }

    /**
     * @dataProvider dataFailedProvider
     * @expectedException \Exception
     */
    public function testFailed($data)
    {
        $service = $this->getService($data);
        $this->assertTrue($service->execute($this->getRequest()));
    }

    /**
     * @dataProvider dataFailedProviderWrongContent
     */
    public function testFailedResponse($data)
    {
        $service = $this->getService($data);

        $this->assertFalse($service->execute($this->getRequest()));
    }

    /**
     * @param $dataProvider
     *
     * @return HandShakeClientService
     */
    protected function getService($dataProvider)
    {
        $body = $this->getMockForAbstractClass(StreamInterface::class);
        $body
            ->method('getContents')
            ->willReturn($dataProvider['content']);

        $response = $this->getMockForAbstractClass(ResponseInterface::class);
        $response
            ->method('getStatusCode')
            ->willReturn($dataProvider['code']);

        $response
            ->method('getBody')
            ->willReturn($body);

        $client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $client
            ->method('send')
            ->willReturn($response);

        $service = $this->getMockBuilder(HandShakeClientService::class)
            ->setMethods(['logError', 'getPlatformService', 'getClient', 'getClass', 'getResource'])
            ->getMockForAbstractClass();

        $service->method('getClient')->willReturn($client);
        $service->method('getResource')->willReturn($this->mockResource());
        $service->method('getClass')->willReturn($this->mockClass());
        $service->method('getPlatformService')->willReturn($this->mockPlatformService());

        $service->setOption(HandShakeClientService::OPTION_REMOTE_AUTH_URL, 'http://removeurl.dev/handshake');
        $serviceLocator = $this->getMockForAbstractClass(ServiceLocatorInterface::class);
        $serviceLocator
            ->method('get')
            ->will($this->onConsecutiveCalls(
                $this->mockPublishingService()
            ));
        $service->setServiceLocator($serviceLocator);


        return $service;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockResource()
    {
        $mock = $this->getMockBuilder(core_kernel_classes_Resource::class)
            ->setMethods(['delete', 'createInstanceWithProperties', 'setType', 'setPropertiesValues'])
            ->disableOriginalConstructor()->getMock();

        return $mock;
    }

    protected function mockClass()
    {
        return $this->getMockBuilder(core_kernel_classes_Class::class)->disableOriginalConstructor()->getMock();
    }
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockPlatformService()
    {
        $service = $this->getMockBuilder(PlatformService::class)->disableOriginalConstructor()->getMock();
        $service
            ->method('getRootClass')
            ->willReturn($this->mockResource());

        return $service;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockPublishingService()
    {
        $service = $this->getMockBuilder(PublishingService::class)->disableOriginalConstructor()->getMock();
        $service
            ->method('findByAction')
            ->willReturn([$this->mockResource()]);

        return $service;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequest()
    {
        $handShakeRequest = $this->getMockBuilder(HandShakeClientRequest::class)->disableOriginalConstructor()->getMock();
        $handShakeRequest
            ->method('getLogin')
            ->willReturn('login');
        $handShakeRequest
            ->method('toArray')
            ->willReturn([
                'login' => 'login',
                'password' => 'password'
            ]);

        return $handShakeRequest;
    }
    /**
     * @return array
     */
    public function dataSuccessProvider()
    {
        return [
            [
                [
                    'code' => 200,
                    'content' => json_encode([
                        'oauthInfo' => [
                            'key' => 'key',
                            'secret' => 'secret',
                            'tokenUrl' => 'tokenUrl',
                        ],
                        'syncUser' => [
                            'id' => '12312',
                            'properties' => '12312',
                        ],
                    ])
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function dataFailedProvider()
    {
        return [
            [
                [
                    'code' => 500,
                    'content' => ''
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    public function dataFailedProviderWrongContent()
    {
        return [
            [
                [
                    'code' => 200,
                    'content' => json_encode([
                        'oauthInfo' => [
                        ],
                        'syncUser' => [

                        ],
                    ])
                ],
            ]
        ];
    }
}
