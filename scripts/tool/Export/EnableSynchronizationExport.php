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


use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\Export\ExportService;
use oat\taoSync\model\Export\Packager\SignatureGenerator;

/**
 * Enables synchronization package export
 *
 * sudo -u www-data php index.php '\oat\taoSync\scripts\tool\Export\EnableSynchronizationExport'
 */
class EnableSynchronizationExport extends InstallAction
{
    public function __invoke($params)
    {
        $report = \common_report_Report::createInfo('EnableSynchronizationExport script started.');
        $exportService = $this->getExportService();
        $exportService->setOption(ExportService::OPTION_IS_ENABLED, true);
        $this->registerService(ExportService::SERVICE_ID, $exportService);
        if (!$this->getServiceLocator()->has(SignatureGenerator::SERVICE_ID)) {
            $report->add(\common_report_Report::createFailure('Please remember to register a manifest signature generator.'));
        }
        $report->add(\common_report_Report::createSuccess('Synchronization package export enabled.'));
        return $report;
    }

    /**
     * @return ExportService
     */
    protected function getExportService()
    {
        return $this->getServiceLocator()->get(ExportService::SERVICE_ID);
    }
}