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

namespace oat\taoSync\model\Export;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\Exception\SyncExportException;
use oat\taoSync\model\Export\Exporter\EntityExporterInterface;
use oat\taoSync\model\Export\Packager\ExportPackagerInterface;

class ExportService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/ExportService';

    const OPTION_TYPES_TO_EXPORT = 'typesToExport';

    const OPTION_EXPORTERS = 'exporters';

    /** @var array */
    private $exporters = [];

    /**
     * @param string $params
     * @return mixed
     * @throws SyncExportException
     */
    public function export($params)
    {
        $packager = $this->getPackagerService();
        $packager->initialize($params);
        foreach ($this->getOption(self::OPTION_TYPES_TO_EXPORT) as $type) {
            $exporter = $this->getExporterByType($type);
            $exporter->export($packager);
        }

        return $packager->finalize();
    }

    /**
     * @return ExportPackagerInterface
     */
    private function getPackagerService()
    {
        return $this->serviceLocator->get(ExportPackagerInterface::SERVICE_ID);
    }

    private function getExporterByType($type)
    {
        if (!array_key_exists($type, $this->exporters)) {

            if (!$this->hasOption(self::OPTION_EXPORTERS)) {
                throw new SyncExportException('Data exporters not configured');
            }

            if (!array_key_exists($type, $this->getOption(self::OPTION_EXPORTERS))) {
                throw new SyncExportException('Data exporter ' . $type . ' is not defined');
            }
            $exporter = $this->getOption(self::OPTION_EXPORTERS)[$type];
            if (!$exporter instanceof EntityExporterInterface) {
                throw new SyncExportException('Type ' . $type . ' has to implement interface ' . EntityExporterInterface::class);
            }
            $this->exporters[$type] = $this->propagate($exporter);
        }
        return $this->exporters[$type];
    }
}
