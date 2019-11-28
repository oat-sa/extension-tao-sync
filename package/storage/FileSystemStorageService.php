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
 * @author Oleksandr Zagovorychev <zagovorichev@1pt.com>
 */

namespace oat\taoSync\package\storage;

use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\taoSyncServer\exception\PackageException;

class FileSystemStorageService extends ConfigurableService implements StorageInterface
{
    const SERVICE_ID = 'taoSyncServer/FileSystemStorageService';

    const FILESYSTEM_ID = 'taoSyncServer';
    const STORAGE_NAME = 'packages';

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->getStorageDir()->exists();
    }

    /**
     * @param array $data
     * @param int $packageName
     * @return bool
     * @throws PackageException
     */
    public function createPackage(array $data, $packageName)
    {
        if (!$this->isValid()) {
            throw new PackageException('Invalid package storage');
        }

        $file = $this->getStorageDir()->getFile($packageName);

        if ($file->exists()) {
            throw new PackageException(sprintf('Package with name  %s already exist', $packageName));
        }

        try {
            return $file->write(json_encode($data));
        } catch (\Exception $e) {
            throw new PackageException($e->getMessage());
        }
    }

    /**
     * @return FileSystemService|array
     */
    private function getFileSystemService()
    {
        return $this->getServiceLocator()
            ->get(FileSystemService::SERVICE_ID);
    }

    /**
     * @return Directory
     */
    private function getStorageDir()
    {
        return $this->getFileSystemService()
            ->getDirectory(self::FILESYSTEM_ID)
            ->getDirectory(self::STORAGE_NAME);

    }

    public function createStorage()
    {
        $this->getFileSystemService()
            ->createFileSystem(self::FILESYSTEM_ID)
            ->createDir(self::STORAGE_NAME);
    }

    /**
     * @return string
     */
    public function getStorageName()
    {
        return static::FILESYSTEM_ID;
    }
}
