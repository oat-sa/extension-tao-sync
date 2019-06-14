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

use common_exception_FileSystemError;
use common_report_Report as Report;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\MachineUsageStatsInterface;

abstract class SpaceUsageStatsService extends ConfigurableService implements MachineUsageStatsInterface
{
    const KEYWORD = 'space_utilization';
    const TITLE = '';

    /** @var array */
    protected $cache = [];

    /**
     * @return Report
     * @throws common_exception_FileSystemError
     */
    public function getReport()
    {
        $values = $this->getUsage();

        $report = new Report(Report::TYPE_INFO);
        $report->setData($values);
        $report->setMessage($this->getUserReadableMessage($values));

        return $report;
    }

    /**
     * @param int $size
     * @param int $precision
     * @return string
     */
    protected function formatBytes($size, $precision = 2)
    {
        if ($size === 0) {
            return 0;
        }
        $base = log($size, 1024);
        $suffixes = ['', 'Kb', 'Mb', 'Gb', 'Tb'];

        return round(1024 ** ($base - floor($base)), $precision) . ' ' . $suffixes[intval($base)];
    }

    /**
     * @param array $values
     * @return string
     */
    protected function getUserReadableMessage(array $values)
    {
        $userReadable = vsprintf(
            static::TITLE . ': %s used, %s still available.',
            array_map(
                function ($v) {
                    return $this->formatBytes($v, 2);
                },
                $values[static::KEYWORD]
            )
        );
        return $userReadable;
    }

    /**
     * @return array
     * @throws common_exception_FileSystemError
     */
    protected function getUsage()
    {
        $directory = $this->getTargetVolume();

        if (!is_dir($directory)) {
            throw new common_exception_FileSystemError(__(sprintf('%s directory not found', $directory)));
        }

        if (!array_key_exists($directory, $this->cache)) {
            $this->cache[$directory] = [
                static::KEYWORD => ['used' => $this->getSpaceUsage(), 'free' => disk_free_space($directory)],
            ];
        }
        return $this->cache[$directory];
    }

    /**
     * @return string
     */
    protected abstract function getTargetVolume();

    /**
     * @return int
     */
    protected abstract function getSpaceUsage();
}
