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

namespace oat\taoSync\model\import;

use oat\generis\model\GenerisRdf;
use oat\generis\model\user\UserRdf;
use oat\tao\model\TaoOntology;
use oat\tao\model\user\import\RdsUserImportService;
use oat\taoSync\model\SyncService;

/**
 * Class SyncUserCsvImporter
 *
 * Implementation of RdsUserImportService to import sync manager resource from a CSV
 *
`
$userImporter = $this->getServiceLocator()->get(UserCsvImporterFactory::SERVICE_ID);
$importer = $userImporter->getImporter(SyncUserCsvImporter::USER_IMPORTER_TYPE);
$report = $importer->import($filePath);
`
 *
 * or by command line:
`
sudo -u www-data php index.php 'oat\tao\scripts\tools\import\ImportUsersCsv' -t sync-manager -f tao/test/user/import/example.csv
`
 *
 */
class SyncUserCsvImporter extends RdsUserImportService
{
    CONST USER_IMPORTER_TYPE = 'sync-manager';

    /**
     * Add test taker role to user to import
     *
     * @param $filePath
     * @param array $extraProperties
     * @param array $options
     * @return \common_report_Report
     * @throws \Exception
     * @throws \common_exception_Error
     */
    public function import($filePath, $extraProperties = [], $options = [])
    {
        $urlAfterLogin =  _url(
            'index',
            'Main',
            'tao',
            array(
                'structure' => 'synchronization',
                'ext'       => 'taoSync'
            )
        );

        $extraProperties[UserRdf::PROPERTY_ROLES] = SyncService::TAO_SYNC_ROLE;
        $extraProperties[TaoOntology::PROPERTY_USER_FIRST_TIME] = GenerisRdf::GENERIS_FALSE;
        $extraProperties[TaoOntology::PROPERTY_USER_LAST_EXTENSION] = str_replace(ROOT_URL, '', $urlAfterLogin);
        $extraProperties['roles'] = SyncService::TAO_SYNC_ROLE;

        return parent::import($filePath, $extraProperties, $options);
    }
}