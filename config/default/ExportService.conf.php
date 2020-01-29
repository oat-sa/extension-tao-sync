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

use oat\taoSync\model\Export\Exporter\ResultsExporter;
use oat\taoSync\model\Export\ExportService;

return new ExportService([
    ExportService::OPTION_EXPORTERS => [
        ResultsExporter::TYPE => new ResultsExporter([
            ResultsExporter::OPTION_BATCH_SIZE => ResultsExporter::DEFAULT_BATCH_SIZE
        ])
    ],
    ExportService::OPTION_IS_ENABLED => false
]);
