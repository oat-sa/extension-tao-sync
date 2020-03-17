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
use Exception;
use GuzzleHttp\Client;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\User\HandShakeClientRequest;
use oat\taoSync\model\User\HandShakeClientService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use oat\generis\test\MockObject;

class HandShakeClientServiceTest extends TestCase
{
    /**
     * @var PublishingService|MockObject
     */
    private $publishingServiceMock;

    /**
     * @var PlatformService|MockObject
     */
    private $platformServiceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->publishingServiceMock = $this->mockPublishingService([$this->mockResource()]);
        $this->platformServiceMock = $this->mockPlatformService($this->mockResource());
    }

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
     */
    public function testFailed($data)
    {
        $this->expectException(Exception::class);
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
     * @dataProvider dataSuccessProvider
     */
    public function testExecute_WhenRemoteConnectionAlreadyExists_ThenItIsUpdated($data)
    {
        $this->publishingServiceMock = $this->mockPublishingService([$this->mockResource()]);
        $resourceMock = $this->mockResource();
        $this->platformServiceMock = $this->mockPlatformService($resourceMock);

        $service = $this->getService($data);

        $this->assertTrue($service->execute($this->getRequest()));
    }

    /**
     * @dataProvider dataSuccessProvider
     */
    public function testExecute_WhenThereAreNoExistingRemoteConnections_ThenOneIsCreated($data)
    {
        $this->publishingServiceMock = $this->mockPublishingService([]);
        $resourceMock = $this->mockClass();
        $this->platformServiceMock = $this->mockPlatformService($resourceMock);

        $service = $this->getService($data);

        $this->assertTrue($service->execute($this->getRequest()));
    }

    /**
     * @dataProvider dataSuccessProvider
     */
    public function testIsHandShakeAlreadyDone($data)
    {
        $service = $this->getService($data);

        $this->assertFalse($service->isHandShakeAlreadyDone());
    }

    /**
     * @dataProvider dataFailedProvider
     */
    public function testIsHandShakeAlreadyDoneTrue($data)
    {
        $service = $this->getService($data);

        $this->assertTrue($service->isHandShakeAlreadyDone());
    }

    public function testIsHandShakeAlreadyDoneTrueWithAlwaysRemoteLogin()
    {
        $service = $this->getService([
            'code' => 200,
            'handshakedone' => 1,
            'content' => '',
            'alwaysRemoteLogin' => true
        ]);
        $this->assertFalse($service->isHandShakeAlreadyDone());
    }

    /**
     * @dataProvider dataSuccessProvider
     */
    public function testMarkHandShakeAlreadyDone($data)
    {
        $service = $this->getService($data);

        $this->assertTrue($service->markHandShakeAlreadyDone());
    }

    public function testMarkHandShakeAlreadyDoneWithAlwaysRemoteLogin()
    {
        $service = $this->getService([
            'code' => 200,
            'handshakedone' => 0,
            'content' => '',
            'alwaysRemoteLogin' => true
        ]);

        $this->assertTrue($service->markHandShakeAlreadyDone());
    }

    /**
     * @param $dataProvider
     *
     * @return HandShakeClientService
     */
    protected function getService($dataProvider = [])
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('getContents')->willReturn($dataProvider['content']);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($dataProvider['code']);
        $response->method('getBody')->willReturn($body);

        $client = $this->createMock(Client::class);
        $client->method('send')->willReturn($response);

        $service = $this->createPartialMock(
            HandShakeClientService::class,
            [
                'logError', 'getPlatformService', 'getClient', 'getClass', 'getResource',
                'hasOption', 'getOption', 'getProperty', 'getOAuth2ClassUri'
            ]
        );

        $service->method('getClient')->willReturn($client);
        $service->method('getResource')->willReturn($this->mockResource());
        $service->method('getClass')->willReturn($this->mockClass());
        $service->method('getPlatformService')->willReturn($this->platformServiceMock);
        $service->method('getOAuth2ClassUri')->willReturn('http://www.tao.lu/Ontologies/TAO.rdf#OauthConsumer');
        $service->method('getProperty')->willReturn($this->mockProperty());

        if (array_key_exists('alwaysRemoteLogin', $dataProvider)) {
            $service->method('hasOption')->willReturn(true);
            $service->method('getOption')->with(HandShakeClientService::OPTION_ALWAYS_REMOTE_LOGIN)->willReturn($dataProvider['alwaysRemoteLogin']);
        } else {
            $service->method('hasOption')->willReturn(false);
        }

        $service->setOption(HandShakeClientService::OPTION_REMOTE_AUTH_URL, 'http://removeurl.dev/handshake');

        $fileSystemMock = $this->mockFileSystem($dataProvider['handshakedone'], array_key_exists('alwaysRemoteLogin', $dataProvider));
        $serviceLocatorMock = $this->getServiceLocatorMock([
            FileSystemService::SERVICE_ID => $fileSystemMock,
            PublishingService::SERVICE_ID => $this->publishingServiceMock,
        ]);

        $service->setServiceLocator($serviceLocatorMock);

        return $service;
    }

    /**
     * @return MockObject
     */
    protected function mockResource()
    {
        return $this->createPartialMock(
            core_kernel_classes_Resource::class,
            ['delete', 'setType', 'setPropertiesValues', 'editPropertyValues']
        );
    }

    protected function mockClass()
    {
        return $this->createMock(core_kernel_classes_Class::class);
    }

    /**
     * @param $resource
     * @return MockObject
     */
    protected function mockPlatformService($resource)
    {
        $service = $this->createMock(PlatformService::class);
        $service
            ->method('getRootClass')
            ->willReturn($resource);

        return $service;
    }

    /**
     * @param $returnValue
     * @return MockObject
     */
    protected function mockPublishingService($returnValue)
    {
        $service = $this->createMock(PublishingService::class);
        $service
            ->method('findByAction')
            ->willReturn($returnValue);

        return $service;
    }

    /**
     * @return MockObject
     */
    protected function mockFileSystem($handShakeDone = 0, $alwaysRemoteLogin = null)
    {
        $fileSystem = $this->createMock(FileSystemService::class);
        $fileMock = $this->createMock(File::class);
        $fileMock
            ->method('read')
            ->willReturn($handShakeDone);

        if ($alwaysRemoteLogin) {
            $fileMock->expects($this->never())->method('put');
        } else {
            $fileMock->method('put')->willReturn(true);
        }

        $directoryMock = $this->createMock(Directory::class);
        $directoryMock
            ->method('getDirectory')
            ->willReturn($directoryMock);
        $directoryMock
            ->method('getFile')
            ->willReturn($fileMock);
        $fileSystem
            ->method('getDirectory')
            ->willReturn($directoryMock);
        $fileSystem
            ->method('getFileSystem')
            ->willReturn($fileMock);

        return $fileSystem;
    }

    /**
     * @return MockObject
     */
    protected function mockProperty()
    {
        return $this->createMock(\core_kernel_classes_Property::class);
    }

    /**
     * @return MockObject
     */
    protected function getRequest()
    {
        $handShakeRequest = $this->createMock(HandShakeClientRequest::class);
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
                    'handshakedone' => 0,
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
                    'handshakedone' => 1,
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
                    'handshakedone' => 0,
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
