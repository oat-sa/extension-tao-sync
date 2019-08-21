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

namespace oat\taoSync\scripts\tool\Import;


use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\import\ImportService;

/**
 * Enables synchronization package import
 *
 * php index.php '\oat\taoSync\scripts\tool\Import\EnableSynchronizationImport'
 */
class EnableSynchronizationImport extends InstallAction
{
    public function __invoke($params)
    {
        $importService = $this->getImportService();
        $importService->setOption(ImportService::OPTION_IS_ENABLED, true);
        $this->registerService(ImportService::SERVICE_ID, $importService);

        return \common_report_Report::createSuccess('Synchronization package import enabled.');
    }

    /**
     * @return ImportService
     */
    protected function getImportService()
    {
        return $this->getServiceLocator()->get(ImportService::SERVICE_ID);
    }
}