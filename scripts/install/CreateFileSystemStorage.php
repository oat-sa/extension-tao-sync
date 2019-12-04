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

namespace oat\taoSync\scripts\install;

use common_Exception;
use common_report_Report;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoSync\package\SyncPackageService;
use oat\taoSync\package\storage\SyncFileSystem;

/**
 * php index.php 'oat\taoSync\scripts\install\CreateFileSystemStorage'
 */
class CreateFileSystemStorage extends InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws common_Exception
     */
    public function __invoke($params)
    {
        $packageStorage = new SyncFileSystem();
        $this->getServiceManager()->propagate($packageStorage);

        $service = $this->getServiceManager()->get(FileSystemService::SERVICE_ID);
        $service->createFileSystem($packageStorage->getStorageName());
        $this->getServiceManager()->register(FileSystemService::SERVICE_ID, $service);

        $packageStorage->createStorage();

        $this->getServiceManager()->register(
            SyncPackageService::SERVICE_ID,
            new SyncPackageService([SyncPackageService::OPTION_STORAGE => $packageStorage])
        );

        return \common_report_Report::createSuccess('FileSystemStorageService successfully created.');
    }
}
