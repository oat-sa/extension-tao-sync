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
use common_report_Report as Report;
use oat\taoSync\model\import\ImportService;
use oat\taoSync\model\Packager\PackagerInterface;

/**
 * Class ImportSynchronizationPackage
 * @package oat\taoSync\scripts\tool\Import
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ImportSynchronizationPackage extends AbstractAction
{
    use FilesystemAwareTrait;

    const FILE_PREFIX = 'syncPackage_';
    const PARAM_FILE = 'file';

    /**
     * @param $params
     * @return Report
     * @throws \oat\taoSync\model\Exception\SyncImportException
     */
    public function __invoke($params)
    {
        $file = $this->getQueueStorageFile($params[static::PARAM_FILE]);
        $syncData = $this->getPackagerService()->unpack($file);
        $importService = $this->getImportService();
        $result = $importService->import($syncData['data'], $syncData['manifest']);
        return Report::createSuccess('Synchronization package successfully imported.', $result);
    }

    /**
     * @return PackagerInterface
     */
    private function getPackagerService()
    {
        return $this->serviceLocator->get(PackagerInterface::SERVICE_ID);
    }

    /**
     * @param UploadedFile $file
     * @return array
     * @throws \common_Exception
     */
    public function prepare(UploadedFile $file)
    {
        $packagePath = $this->saveStreamToStorage($file->getStream(), self::FILE_PREFIX . microtime(true) . '.zip');
        return [static::PARAM_FILE => $packagePath];
    }

    /**
     * @return ImportService
     */
    private function getImportService()
    {
        return $this->getServiceLocator()
            ->get(ImportService::SERVICE_ID);
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
