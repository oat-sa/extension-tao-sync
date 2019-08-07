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

namespace oat\taoSync\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\Export\Exporter\ResultsExporter;
use oat\taoSync\model\Export\ExportService;
use oat\taoSync\model\Export\Packager\ExportPackagerInterface;
use oat\taoSync\model\Export\Packager\ExportZipPackager;

/**
 * Class RegisterExportService
 *
 * Register the sync export service
 *
 * @package oat\taoSync\scripts\install
 */
class RegisterExportService extends InstallAction
{
    public function __invoke($params)
    {
        $resultsExporter = new ResultsExporter([
            ResultsExporter::OPTION_BATCH_SIZE => ResultsExporter::DEFAULT_BATCH_SIZE,
        ]);
        $this->registerService(ResultsExporter::SERVICE_ID, $resultsExporter);

        $exportPackager = new ExportZipPackager();
        $this->registerService(ExportPackagerInterface::SERVICE_ID, $exportPackager);

        $exportService = new ExportService([
            ExportService::OPTION_TYPES_TO_EXPORT => [ResultsExporter::TYPE],
            ExportService::OPTION_EXPORTERS => [
                ResultsExporter::TYPE => $resultsExporter
            ],
        ]);
        $this->registerService(ExportService::SERVICE_ID, $exportService);

        return \common_report_Report::createSuccess('ExportService successfully registered.');
    }
}