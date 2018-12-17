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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\scripts\tool\synchronizationLog;


use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\storage\RdsSyncLogStorage;

/**
 * Class RegisterRdsSyncLogStorage
 * @package oat\taoSync\scripts\tool\synchronizationLog
 */
class RegisterRdsSyncLogStorage extends InstallAction
{
    /**
     * @inheritdoc
     */
    public function __invoke($params)
    {
        $storage = new RdsSyncLogStorage([
            RdsSyncLogStorage::OPTION_PERSISTENCE_ID => 'default'
        ]);
        $this->getServiceManager()->register(RdsSyncLogStorage::SERVICE_ID, $storage);
        $this->createTable($storage->getPersistence());

        return \common_report_Report::createSuccess('RdsSyncLogStorage successfully registered.');
    }

    /**
     * @param \common_persistence_SqlPersistence $persistence
     */
    public function createTable($persistence)
    {
        /** @var \common_persistence_sql_pdo_mysql_SchemaManager $schemaManager */
        $schemaManager = $persistence->getSchemaManager();
        $fromSchema = $schemaManager->createSchema();
        $toSchema = clone $fromSchema;
        $tableName = RdsSyncLogStorage::TABLE_NAME;

        if(false === $fromSchema->hasTable($tableName)) {
            $table = $toSchema->createTable($tableName);
            $table->addOption('engine', 'InnoDB');
            $table->addColumn(RdsSyncLogStorage::COLUMN_ID, 'integer', ["notnull" => true, "autoincrement" => true, "unsigned" => true]);
            $table->addColumn(RdsSyncLogStorage::COLUMN_CLIENT_ID, 'string', ["notnull" => true, "length" => 50]);
            $table->addColumn(RdsSyncLogStorage::COLUMN_SYNC_ID, 'integer', ["notnull" => true, "unsigned" => true]);
            $table->addColumn(RdsSyncLogStorage::COLUMN_ORGANIZATION_ID, 'string', ["notnull" => true, "length" => 50]);
            $table->addColumn(RdsSyncLogStorage::COLUMN_DATA, 'text', ["notnull" => false, "default" => null]);
            $table->addColumn(RdsSyncLogStorage::COLUMN_STATUS, 'string', ["notnull" => true, "length" => 20]);
            $table->addColumn(RdsSyncLogStorage::COLUMN_REPORT, 'text', ["notnull" => false, "default" => null]);
            $table->addColumn(RdsSyncLogStorage::COLUMN_STARTED_AT, 'datetime', ['notnull' => true]);
            $table->addColumn(RdsSyncLogStorage::COLUMN_FINISHED_AT, 'datetime', ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addIndex([RdsSyncLogStorage::COLUMN_STATUS], "{$tableName}_IDX_status");
            $table->addIndex([RdsSyncLogStorage::COLUMN_STARTED_AT], "{$tableName}_IDX_created_at");

            $queries = $persistence->getPlatForm()->getMigrateSchemaSql($fromSchema, $toSchema);
            foreach ($queries as $query) {
                $persistence->exec($query);
            }
        }
    }
}
