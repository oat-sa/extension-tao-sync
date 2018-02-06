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

namespace oat\taoSync\scripts\install;

use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\history\SyncHistoryService;

class InstallSynchronisationHistory extends InstallAction
{
    public function __invoke($params)
    {
        $persistenceId = 'default';

        $syncHistoryService = new SyncHistoryService(array(
            SyncHistoryService::OPTION_PERSISTENCE => $persistenceId
        ));

        $this->registerService(SyncHistoryService::SERVICE_ID, $syncHistoryService);

        $persistence = $syncHistoryService->getPersistence();

        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $tableResults = $schema->createtable(SyncHistoryService::SYNC_TABLE);
            $tableResults->addOption('engine', 'MyISAM');

            $tableResults->addColumn(SyncHistoryService::SYNC_ID, 'integer', ['autoincrement' => true]);
            $tableResults->addColumn(SyncHistoryService::SYNC_NUMBER, 'integer', ['notnull' => true, 'length' => 16]);
            $tableResults->addColumn(SyncHistoryService::SYNC_ACTION, 'string', ['notnull' => true, 'length' => 32]);
            $tableResults->addColumn(SyncHistoryService::SYNC_ENTITY_ID, 'string', ['notnull' => true, 'length' => 128]);
            $tableResults->addColumn(SyncHistoryService::SYNC_ENTITY_TYPE, 'string', ['notnull' => true, 'length' => 32]);
            $tableResults->addColumn(SyncHistoryService::SYNC_TIME, 'datetime', ['notnull' => true,]);

            $tableResults->setPrimaryKey(array(SyncHistoryService::SYNC_ID));

            $tableResults->addIndex([SyncHistoryService::SYNC_ENTITY_ID], 'entity_id');

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }

        } catch(SchemaException $e) {
            \common_Logger::i($e->getMessage());
            \common_Logger::i('Database Schema already up to date.');
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Synchronisation table successfully created');
    }

}