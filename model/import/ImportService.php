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

namespace oat\taoSync\model\import;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\import\Importer\EntityImporterInterface;
use oat\taoSync\model\Exception\SyncImportException;

/**
 * Class ImportService
 * @package oat\taoSync\model\import
 * @author Aleh Hutnikau, <hutnikau@1pt.com>
 */
class ImportService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/ImportService';
    const OPTION_IMPORTERS = 'importers';
    const OPTION_IS_ENABLED = 'isEnabled';

    /** @var EntityImporterInterface[] */
    private $importers = [];

    /**
     * @param array $data
     * @param array $manifest
     * @return array
     * @throws SyncImportException
     */
    public function import(array $data, array $manifest)
    {
        $importers = $this->getImporters();
        $results = [];
        foreach ($data as $key => $syncData) {
            if (!isset($importers[$key])) {
                throw new SyncImportException($key . ' importer is not configured');
            }
            $results[$key] = $importers[$key]->import($syncData, $manifest);
        }
        return $results;
    }

    /**
     * @return EntityImporterInterface[]
     * @throws SyncImportException
     */
    private function getImporters()
    {
        if (!empty($this->importers)) {
            return $this->importers;
        }

        $importers = $this->getOption(self::OPTION_IMPORTERS);

        foreach ($importers as $key => $importerParams) {
            if (!is_subclass_of($importerParams[EntityImporterInterface::OPTION_CLASS], EntityImporterInterface::class)) {
                throw new SyncImportException('Importer is not instance of ' . EntityImporterInterface::class);
            }
            $importer = new $importerParams[EntityImporterInterface::OPTION_CLASS](
                $importerParams[EntityImporterInterface::OPTION_PARAMETERS]
            );
            $this->propagate($importer);
            $this->importers[$key] = $importer;
        }
        return $this->importers;
    }
}
