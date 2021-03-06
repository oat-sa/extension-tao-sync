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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\scripts\tool;

use Doctrine\DBAL\Schema\SchemaException;
use oat\generis\model\user\UserFactoryServiceInterface;
use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\history\byOrganisationId\DataSyncHistoryByOrgIdService;
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\User\HandShakeClientService;
use oat\taoSync\model\User\ScopedToOrganisationAuthAdapter;

/**
 * Tool to setup the platform to accept synchronisation from multiple sync manager
 * Testtaker can connect only if they are part of last synchronisation
 *
 * sudo -u www-data php index.php '\oat\taoSync\scripts\tool\SetupMultiSynchronisation'
 */
class SetupMultiSynchronisation extends InstallAction
{
    public function __invoke($params)
    {
        $handShakeService = $this->getServiceLocator()->get(HandShakeClientService::SERVICE_ID);
        $handShakeService->setOption(HandShakeClientService::OPTION_ALWAYS_REMOTE_LOGIN, true);
        $this->registerService(HandShakeClientService::SERVICE_ID, $handShakeService);

        $service = new DataSyncHistoryByOrgIdService($this->getServiceLocator()->get(DataSyncHistoryService::SERVICE_ID)->getOptions());
        $this->registerService(DataSyncHistoryService::SERVICE_ID, $service);

        $persistence = $service->getPersistence();

         /** @var \common_persistence_sql_SchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $syncTable = $schema->getTable(DataSyncHistoryByOrgIdService::SYNC_TABLE);
            $syncTable->addColumn(
                DataSyncHistoryByOrgIdService::SYNC_ORG_ID,
                'string',
                ['length' => 255, 'notnull' => true, 'default' => '']
            );

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }
        } catch (SchemaException $e) {
            \common_Logger::i('Database Schema already up to date.');
        }

        $this->getServiceLocator()
            ->get(\common_ext_ExtensionsManager::SERVICE_ID)
            ->getExtensionById('generis')
            ->setConfig('auth', [
                [
                    'driver' => ScopedToOrganisationAuthAdapter::class,
                    'user_factory' => UserFactoryServiceInterface::SERVICE_ID,
                ]
            ]);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Multi synchronisation has been successfully enabled.');
    }
}
