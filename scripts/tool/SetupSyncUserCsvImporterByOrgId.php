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
 * Copyright (c) 2018 Open Assessment Technologies SA
 */

namespace oat\taoSync\scripts\tool;

use oat\oatbox\extension\InstallAction;
use oat\tao\model\user\import\OntologyUserMapper;
use oat\tao\model\user\import\UserCsvImporterFactory;
use oat\tao\model\user\import\UserMapper;
use oat\taoSync\model\import\SyncUserCsvImporter;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;

class SetupSyncUserCsvImporterByOrgId extends InstallAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        /** @var UserCsvImporterFactory $factory */
        $factory = $this->getServiceManager()->get(UserCsvImporterFactory::SERVICE_ID);

        $mappers = $factory->getOption(UserCsvImporterFactory::OPTION_MAPPERS);
        $syncUserOptions = $mappers[SyncUserCsvImporter::USER_IMPORTER_TYPE];

        $mapper = $factory->getImporter(SyncUserCsvImporter::USER_IMPORTER_TYPE)->getMapper();
        if (!$mapper) {
            $mapper = new OntologyUserMapper();
            $schema = $factory->getOption(UserCsvImporterFactory::OPTION_DEFAULT_SCHEMA);
        } else {
            $schema = $mapper->getOption(UserMapper::OPTION_SCHEMA);
        }

        $schema[OntologyUserMapper::OPTION_SCHEMA_MANDATORY]['organisation id'] = TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY;
        $mapper->setOption(UserMapper::OPTION_SCHEMA, $schema);

        $syncUserOptions[UserCsvImporterFactory::OPTION_MAPPERS_MAPPER] = $mapper;
        $mappers[SyncUserCsvImporter::USER_IMPORTER_TYPE] = $syncUserOptions;

        $factory->setOption(UserCsvImporterFactory::OPTION_MAPPERS, $mappers);
        $this->getServiceManager()->register(UserCsvImporterFactory::SERVICE_ID, $factory);

        return \common_report_Report::createSuccess('SetupSyncUserCsvImporter successfully registered.');
    }
}