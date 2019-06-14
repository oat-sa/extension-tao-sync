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
 * Copyright (c) 2019. (original work) Open Assessment Technologies SA;
 */
namespace oat\taoSync\model\monitoring;

use oat\oatbox\filesystem\FileSystemService;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DataSpaceUsageService extends SpaceUsageStatsService
{
    const KEYWORD = 'disk_space_utilization';
    const TITLE = 'Disk space';

    /**
     * @inheritDoc
     */
    protected function getSpaceUsage()
    {
        $size = 0;
        $dir = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->getTargetVolume(),
                RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($dir as $fileInfo) {
            $size += $fileInfo->getSize();
        }
        return $size;
    }

    /**
     * @inheritDoc
     */
    protected function getTargetVolume()
    {
        /** @var FileSystemService $fs */
        $fs = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
        $path = $fs->getOption(FileSystemService::OPTION_FILE_PATH);
        return $path;
    }
}
