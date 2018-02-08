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
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\history\ResultSyncHistoryService;

class InstallSynchronisationHistory extends InstallAction
{
    public function __invoke($params)
    {
        $persistenceId = 'default';

        $this->registerService(
            DataSyncHistoryService::SERVICE_ID,
            new DataSyncHistoryService(array(
                DataSyncHistoryService::OPTION_PERSISTENCE => $persistenceId
            ))
        );

        $this->registerService(
            ResultSyncHistoryService::SERVICE_ID,
            new ResultSyncHistoryService(array(
                ResultSyncHistoryService::OPTION_PERSISTENCE => $persistenceId
            ))
        );

        $persistence = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);

        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $tableData = $schema->createtable(DataSyncHistoryService::SYNC_TABLE);
            $tableData->addOption('engine', 'MyISAM');

            $tableData->addColumn(DataSyncHistoryService::SYNC_ID, 'integer', ['autoincrement' => true]);
            $tableData->addColumn(DataSyncHistoryService::SYNC_NUMBER, 'integer', ['notnull' => true, 'length' => 16]);
            $tableData->addColumn(DataSyncHistoryService::SYNC_ACTION, 'string', ['notnull' => true, 'length' => 32]);
            $tableData->addColumn(DataSyncHistoryService::SYNC_ENTITY_ID, 'string', ['notnull' => true, 'length' => 128]);
            $tableData->addColumn(DataSyncHistoryService::SYNC_ENTITY_TYPE, 'string', ['notnull' => true, 'length' => 32]);
            $tableData->addColumn(DataSyncHistoryService::SYNC_TIME, 'datetime', ['notnull' => true,]);

            $tableData->setPrimaryKey(array(DataSyncHistoryService::SYNC_ID));

            $tableData->addIndex([DataSyncHistoryService::SYNC_NUMBER], 'idx_sync_data_number');
            $tableData->addIndex([DataSyncHistoryService::SYNC_ENTITY_ID], 'idx_sync_data_entity_id');
            $tableData->addIndex([DataSyncHistoryService::SYNC_ENTITY_TYPE], 'idx_sync_data_entity_type');
            $tableData->addIndex([DataSyncHistoryService::SYNC_ACTION], 'idx_sync_data_action');


            $tableResults = $schema->createtable(ResultSyncHistoryService::SYNC_RESULT_TABLE);
            $tableResults->addOption('engine', 'MyISAM');

            $tableResults->addColumn(ResultSyncHistoryService::SYNC_RESULT_ID, 'string');
            $tableResults->addColumn(ResultSyncHistoryService::SYNC_RESULT_STATUS, 'string', ['notnull' => true, 'length' => 32]);
            $tableResults->addColumn(ResultSyncHistoryService::SYNC_RESULT_TIME, 'datetime', ['notnull' => true,]);

            $tableResults->setPrimaryKey(array(ResultSyncHistoryService::SYNC_RESULT_ID));

            $tableResults->addIndex([ResultSyncHistoryService::SYNC_RESULT_ID], 'idx_sync_result_id');
            $tableResults->addIndex([ResultSyncHistoryService::SYNC_RESULT_STATUS], 'idx_sync_result_status');

            $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }

        } catch(SchemaException $e) {
            \common_Logger::i($e->getMessage());
            \common_Logger::i('Database Schema already up to date.');
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Synchronisation storage successfully created');
    }

}