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

namespace oat\taoSync\scripts\tool\Export;

use oat\oatbox\extension\AbstractAction;
use oat\oatbox\filesystem\FileSystemService;
use oat\tao\model\taskQueue\Task\FilesystemAwareTrait;
use oat\taoSync\model\Export\ExportService;

class ExportSynchronizationPackage extends AbstractAction
{
    use FilesystemAwareTrait;

    const FILE_PREFIX = 'SyncExport_';

    public function __invoke($params)
    {
        try {
            $archivePath = $this->getExportService()->export($params);
            $packagePath = $this->saveFileToStorage($archivePath, self::FILE_PREFIX . time() . '.zip');

            return \common_report_Report::createSuccess('Synchronization package export completed.', $packagePath);
        } catch (\Exception $e) {
            $this->logAlert($e->getMessage());

            return \common_report_Report::createFailure('Synchronization package export failed.');
        }
    }

    /**
     * @return ExportService
     */
    public function getExportService()
    {
        return $this->serviceLocator->get(ExportService::SERVICE_ID);
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
