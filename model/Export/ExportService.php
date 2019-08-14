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
use oat\taoSync\model\Packager\PackagerInterface;

class ExportService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/ExportService';

    const OPTION_IS_ENABLED = 'isEnabled';

    const OPTION_EXPORTERS = 'exporters';

    /**
     * @param string $params
     * @return mixed
     * @throws SyncExportException
     */
    public function export($params)
    {
        $packager = $this->getPackagerService();
        $packager->initialize($params);
        foreach ($this->getConfiguredExporters() as $exporter) {
            $exporter->export($packager);
        }

        return $packager->finalize();
    }

    /**
     * @return PackagerInterface
     */
    private function getPackagerService()
    {
        return $this->getServiceLocator()->get(PackagerInterface::SERVICE_ID);
    }

    /**
     * @return EntityExporterInterface[]
     * @throws SyncExportException
     */
    private function getConfiguredExporters()
    {
        if (!$this->hasOption(self::OPTION_EXPORTERS)) {
            throw new SyncExportException('Synchronization data exporters not configured');
        }
        $exporters = [];
        foreach ($this->getOption(self::OPTION_EXPORTERS) as $type => $exporter) {
            if (!$exporter instanceof EntityExporterInterface) {
                throw new SyncExportException('Exporter type ' . $type . ' has to implement interface ' . EntityExporterInterface::class);
            }
            $exporters[$type] = $this->propagate($exporter);
        }

        return $exporters;
    }
}
