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

namespace oat\taoSync\test\unit\package;

use League\Flysystem\FilesystemInterface;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoSync\model\Exception\SyncPackageException;
use oat\taoSync\package\SyncPackageService;

class SyncPackageServiceTest extends TestCase
{
    /**
     * @var FileSystemService|MockObject
     */
    private $fileSystemServiceMock;

    /**
     * @var FilesystemInterface|MockObject
     */
    private $filesystemInterfaceMock;

    /**
     * @var FilesystemInterface|MockObject
     */
    private $serviceLocatorMock;

    /**
     * @var SyncPackageService
     */
    private $service;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->fileSystemServiceMock = $this->createMock(FileSystemService::class);
        $this->filesystemInterfaceMock = $this->createMock(FilesystemInterface::class);
        $this->serviceLocatorMock = $this->getServiceLocatorMock(
            [FileSystemService::SERVICE_ID => $this->fileSystemServiceMock]
        );

        $this->service = (new SyncPackageService())->setServiceLocator($this->serviceLocatorMock);
    }

    public function testCreateStorage()
    {
        $this->fileSystemServiceMock->expects($this->once())
            ->method('getFileSystem')
            ->with('synchronisation')
            ->willReturn($this->filesystemInterfaceMock);

        $this->filesystemInterfaceMock->expects($this->once())->method('createDir')->with('packages');

        $this->service->createStorage();
    }

    public function testCreatePackage()
    {
        $fileMock = $this->getFileMock();

        $fileMock->expects($this->once())
            ->method('write')
            ->with('{"key":"val","key2":"val2"}')
            ->willReturn(true);

        $fileMock->expects($this->once())
            ->method('getPrefix')
            ->willReturn('packageName');

        $this->assertEquals(
            'synchronisation'.DIRECTORY_SEPARATOR.'packageName',
            $this->service->createPackage(['key' => 'val', 'key2' => 'val2'], 'packageName', 'orgId')
        );
    }

    public function testCreatePackageWithFail()
    {
        $fileMock = $this->getFileMock();

        $fileMock->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $fileMock->expects($this->once())
            ->method('write')
            ->with('{"key":"val","key2":"val2"}')
            ->willReturn(false);

        $this->assertNull(
            $this->service->createPackage(['key' => 'val', 'key2' => 'val2'], 'packageName', 'orgId')
        );
    }

    /**
     * @return File|MockObject
     */
    private function getFileMock()
    {
        $directoryOrg = $this->getOrgDirectory();

        $fileMock = $this->createMock(File::class);

        $directoryOrg->expects($this->once())
            ->method('getFile')
            ->with('packageName')
            ->willReturn($fileMock);

        return $fileMock;
    }

    public function testCreatePackageWithExistFile()
    {
        $fileMock = $this->getFileMock();

        $fileMock->expects($this->once())
            ->method('exists')
            ->willReturn(true);

        $this->expectException(SyncPackageException::class);

        $this->service->createPackage(['key' => 'val', 'key2' => 'val2'], 'packageName', 'orgId');
    }

    /**
     * @return Directory|MockObject
     */
    private function getOrgDirectory()
    {
        $directory = $this->getMockBuilder(Directory::class)
            ->setConstructorArgs(['synchronisation', 'pref'])->getMock();
        $directoryPackages = $this->getMockBuilder(Directory::class)
            ->setConstructorArgs(['packages', 'pref'])->getMock();
        $directoryOrg = $this->getMockBuilder(Directory::class)
            ->setConstructorArgs(['orgId', 'pref'])->getMock();

        $this->fileSystemServiceMock->expects($this->once())
            ->method('getDirectory')
            ->with('synchronisation')
            ->willReturn($directory);

        $directory->expects($this->once())
            ->method('getDirectory')
            ->with('packages')
            ->willReturn($directoryPackages);

        $directoryPackages->expects($this->once())
            ->method('getDirectory')
            ->with('orgId')
            ->willReturn($directoryOrg);

        return $directoryOrg;
    }

    public function testGetSyncDirectory()
    {
        $directory = $this->createMock(Directory::class);

        $this->fileSystemServiceMock
            ->expects($this->once())
            ->method('getDirectory')
            ->with('synchronisation')
            ->willReturn($directory);

        $this->assertSame($directory, $this->service->getSyncDirectory());
    }
}
