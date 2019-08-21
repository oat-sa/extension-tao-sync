<?php
/**
 * Default config header created during install
 */

use oat\taoSync\model\import\ImportService;
use oat\taoSync\model\import\Importer\EntityImporterInterface;
use oat\taoSync\model\import\Importer\ResultsImporter;

return new ImportService([
    ImportService::OPTION_IMPORTERS => [
        ResultsImporter::TYPE => [
            EntityImporterInterface::OPTION_CLASS => ResultsImporter::class,
            EntityImporterInterface::OPTION_PARAMETERS => []
        ]
    ],
    ImportService::OPTION_IS_ENABLED => false
]);
