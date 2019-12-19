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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 * @author Yuri Filippovich
 */

namespace oat\taoSync\package;

use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\Exception\SyncPackageException;

class SyncPackageService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncPackageService';
    const OPTION_STORAGE = 'storage';
    const FILESYSTEM_ID = 'synchronisation';
    const STORAGE_NAME = 'packages';

    /**
     * @param array $data
     * @param int $packageName
     * @param int $orgId
     * @return string|null
     * @throws SyncPackageException
     */
    public function createPackage(array $data, $packageName, $orgId)
    {
        $file = $this->getStorageDir($orgId)->getFile($packageName);

        if ($file->exists()) {
            throw new SyncPackageException(sprintf('Package with name %s already exist', $packageName));
        }

        try {
             if ($file->write(json_encode($data))) {
                 return self::FILESYSTEM_ID . DIRECTORY_SEPARATOR . $file->getPrefix();
             }
        } catch (\Exception $e) {
            throw new SyncPackageException($e->getMessage());
        }
    }

    /**
     * @return FileSystemService|array
     */
    private function getFileSystemService()
    {
        return $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
    }

    /**
     * @param int $orgId
     * @return Directory
     * @throws SyncPackageException
     */
    private function getStorageDir($orgId)
    {
        try {
            $directory = $this->getFileSystemService()
                ->getDirectory(self::FILESYSTEM_ID)
                ->getDirectory(self::STORAGE_NAME)
                ->getDirectory($orgId);

            if (!$directory->exists()) {
                $this->getFileSystemService()
                    ->getFileSystem(self::FILESYSTEM_ID)
                    ->createDir(self::STORAGE_NAME . DIRECTORY_SEPARATOR . $orgId);
            }
            return $directory;
        } catch (\Exception $e) {
            throw new SyncPackageException($e->getMessage());
        }
    }

    /**
     * @throws SyncPackageException
     */
    public function createStorage()
    {
        try {
            $this->getFileSystemService()
                ->getFileSystem(self::FILESYSTEM_ID)
                ->createDir(self::STORAGE_NAME);
        } catch (\Exception $e) {
            throw new SyncPackageException($e->getMessage());
        }
    }
}
