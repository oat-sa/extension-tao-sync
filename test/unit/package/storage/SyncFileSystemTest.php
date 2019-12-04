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

namespace oat\taoSync\test\unit\package\storage;

use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoSync\model\Exception\SyncPackageException;
use oat\taoSync\package\storage\SyncFileSystem;
use Zend\ServiceManager\ServiceLocatorInterface;

class SyncFileSystemTest extends TestCase
{
    /**
     * @var ServiceLocatorInterface|MockObject
     */
    private $serviceLocatorMock;

    /**
     * @var FileSystemService|MockObject
     */
    private $fileSystemServiceMock;

    /**
     * @var Directory|MockObject
     */
    private $directoryMock;

    /**
     * @var Directory|MockObject
     */
    private $syncDirectoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->directoryMock = $this->createMock(Directory::class);
        $this->syncDirectoryMock = $this->createMock(Directory::class);

        $this->fileSystemServiceMock = $this->createMock(FileSystemService::class);

        $this->fileSystemServiceMock
            ->method('getDirectory')
            ->with('taoSync')
            ->willReturn( $this->syncDirectoryMock);


        $this->syncDirectoryMock
            ->method('getDirectory')
            ->with('packages')
            ->willReturn($this->directoryMock);

        $this->serviceLocatorMock = $this->getServiceLocatorMock(
            ['generis/filesystem' => $this->fileSystemServiceMock]
        );
    }

    public function testIsValid()
    {
        $syncFileSystem = new SyncFileSystem();

        $syncFileSystem->setServiceLocator($this->serviceLocatorMock);

        $this->directoryMock
            ->expects($this->exactly(2))
            ->method('exists')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->assertFalse($syncFileSystem->isValid());
        $this->assertTrue($syncFileSystem->isValid());
    }

    public function testCreatePackage()
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())->method('write')->with('{"a":"b","c":"e"}')->willReturn(true);
        $file->expects($this->once())->method('exists')->willReturn(false);
        $this->directoryMock->expects($this->once())->method('exists')->willReturn(true);
        $this->directoryMock->method('getFile')->with('filename')->willReturn($file);

        $syncFileSystem = new SyncFileSystem();
        $syncFileSystem->setServiceLocator($this->serviceLocatorMock);

        $this->assertTrue($syncFileSystem->createPackage(['a' => 'b', 'c' => 'e'], 'filename'));
    }

    public function testCreatePackageWithExistFile()
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())->method('exists')->willReturn(true);
        $this->directoryMock->expects($this->once())->method('exists')->willReturn(true);
        $this->directoryMock->method('getFile')->with('filename')->willReturn($file);

        $syncFileSystem = new SyncFileSystem();
        $syncFileSystem->setServiceLocator($this->serviceLocatorMock);
        $this->expectException(SyncPackageException::class);

        $syncFileSystem->createPackage(['a' => 'b', 'c' => 'e'], 'filename');
    }

    public function testCreatePackageWithInvalidDirectory()
    {
        $this->directoryMock->expects($this->once())->method('exists')->willReturn(false);

        $syncFileSystem = new SyncFileSystem();
        $syncFileSystem->setServiceLocator($this->serviceLocatorMock);
        $this->expectException(SyncPackageException::class);

        $syncFileSystem->createPackage(['a' => 'b', 'c' => 'e'], 'filename');
    }
}
