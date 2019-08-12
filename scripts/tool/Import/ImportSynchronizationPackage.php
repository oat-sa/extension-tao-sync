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

namespace oat\taoSync\scripts\tool\Import;

use oat\oatbox\extension\AbstractAction;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\taskQueue\Task\FilesystemAwareTrait;
use GuzzleHttp\Psr7\UploadedFile;

class ImportSynchronizationPackage extends AbstractAction
{
    use FilesystemAwareTrait;

    const FILE_PREFIX = 'syncPackage_';

    public function __invoke($params)
    {
        $file = $this->getQueueStorageFile($params['file']);

    }

    /**
     * @param UploadedFile $file
     * @return array
     * @throws \common_Exception
     */
    public function prepare(UploadedFile $file)
    {
        $packagePath = $this->saveStreamToStorage($file->getStream(), self::FILE_PREFIX . microtime(true) . '.zip');
        return ['file' => $packagePath];
    }

    /**
     * @see FilesystemAwareTrait::getFileSystemService()
     */
    protected function getFileSystemService()
    {
        return $this->getServiceLocator()
            ->get(FileSystemService::SERVICE_ID);
    }
}