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

use oat\generis\test\TestCase;
use oat\taoSync\package\storage\SyncFileSystem;
use oat\taoSync\package\SyncPackageService;

class SyncPackageServiceTest extends TestCase
{
    public function testCreatePackage()
    {
       $syncFileSystem = $this->createMock(SyncFileSystem::class);

       $syncPackageService = new SyncPackageService(
           [
               SyncPackageService::OPTION_STORAGE => $syncFileSystem
           ]
       );

        $syncPackageService->setServiceLocator($this->getServiceLocatorMock());

       $data = ['key' => 'value'];
       $packageName = 'packageName';

       $syncFileSystem->expects($this->once())
           ->method('createPackage')
           ->with($data, $packageName)->willReturn(true);

        $this->assertTrue($syncPackageService->createPackage($data, $packageName));
    }
}
