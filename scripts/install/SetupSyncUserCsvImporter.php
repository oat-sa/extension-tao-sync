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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

namespace oat\taoSync\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\tao\model\user\import\UserCsvImporterFactory;
use oat\taoSync\model\import\SyncUserCsvImporter;

class SetupSyncUserCsvImporter extends InstallAction
{
    public function __invoke($params)
    {
        $importerFactory = $this->getServiceLocator()->get(UserCsvImporterFactory::SERVICE_ID);
        $typeOptions = $importerFactory->getOption(UserCsvImporterFactory::OPTION_MAPPERS);
        $typeOptions[SyncUserCsvImporter::USER_IMPORTER_TYPE] = array(
            UserCsvImporterFactory::OPTION_MAPPERS_IMPORTER => new SyncUserCsvImporter()
        );
        $importerFactory->setOption(UserCsvImporterFactory::OPTION_MAPPERS, $typeOptions);
        $this->registerService(UserCsvImporterFactory::SERVICE_ID, $importerFactory);
        return \common_report_Report::createSuccess('SyncUser csv importer successfully registered.');
    }

}