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

use common_report_Report;
use Doctrine\DBAL\Schema\SchemaException;
use oat\oatbox\extension\script\ScriptAction;
use oat\taoSync\model\history\byOrganisationId\DataSyncHistoryByOrgIdService;
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\ui\FormFieldsService;

/**
 *  Tool that would take about renaming column and migration data do be compatible with postgres
 *
 * sudo -u www-data php index.php '\oat\taoSync\scripts\tool\RenameColumnOrgId'
 *
 */
class RenameColumnOrgId extends ScriptAction
{
    private $migrate;
    private $cleanup;

    const COLUMN_OLD = 'organisationId';

    protected function run()
    {

        try {
            $this->init();
        } catch (\Exception $e) {
            return common_report_Report::createFailure($e->getMessage());
        }
        $result = common_report_Report::createSuccess('Data migration execution results:');

        /** @var DataSyncHistoryByOrgIdService $service */
        $service = $this->getServiceLocator()->get(DataSyncHistoryService::SERVICE_ID);

        $persistence = $service->getPersistence();

        /** @var \common_persistence_sql_SchemaManager $schemaManager */
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $syncTable = $schema->getTable(DataSyncHistoryByOrgIdService::SYNC_TABLE);
            if ($this->migrate) {
                if ($syncTable->hasColumn(self::COLUMN_OLD)) {
                    try {
                        $syncTable->addColumn(DataSyncHistoryByOrgIdService::SYNC_ORG_ID, 'integer', ['length' => 11]);
                        $this->applySchema($persistence, $fromSchema, $schema);
                        $result->add(common_report_Report::createSuccess('Column has been created'));
                    } catch (SchemaException $e) {
                        $result->add(common_report_Report::createInfo($e->getMessage()));
                    }
                    $result->add($this->migrateData($persistence));
                } else {
                    $result->add(common_report_Report::createInfo('Looks like you\'ve already have column in place'));
                }
            }
            if ($this->cleanup) {
                if ($syncTable->hasColumn(DataSyncHistoryByOrgIdService::SYNC_ORG_ID)) {
                    $syncTable->dropColumn(self::COLUMN_OLD);
                    $this->applySchema($persistence, $fromSchema, $schema);
                    $result->add(common_report_Report::createSuccess('Column has been dropped'));
                } else {
                    $result->add(common_report_Report::createFailure('New column must exist! Please run --migrate first'));
                }
            }
        } catch (\Exception $e) {
            $result = common_report_Report::createFailure($e->getMessage());
        }

        return $result;
    }

    /**
     * @param $persistence
     * @param $fromSchema
     * @param $schema
     */
    private function applySchema($persistence, $fromSchema, $schema)
    {
        $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
        foreach ($queries as $query) {
            $persistence->exec($query);
        }
    }

    /**
     * @param \common_persistence_SqlPersistence $persistence
     * @return common_report_Report
     */
    private function migrateData(\common_persistence_SqlPersistence $persistence)
    {
        $builder = $persistence->getPlatForm()->getQueryBuilder();
        $q = $builder
            ->update(DataSyncHistoryByOrgIdService::SYNC_TABLE)
            ->set(DataSyncHistoryByOrgIdService::SYNC_ORG_ID, self::COLUMN_OLD);
        $result = $q->execute();
        return common_report_Report::createSuccess(__('%s rows has been migrated', $result));
    }

    protected function provideOptions()
    {
        return [
            'migrate' => [
                'longPrefix' => 'migrate',
                'required' => false,
                'flag' => true,
                'description' => 'Creates column and move data',
            ],
            'cleanup' => [
                'longPrefix' => 'cleanup',
                'required' => false,
                'flag' => true,
                'description' => 'Remove obsolete column(only if migration happened ',
            ], 'help' => [
                'longPrefix' => 'help',
                'required' => false,
                'flag' => true,
                'description' => 'Remove obsolete column(only if migration happened ',
            ],

        ];
    }

    protected function provideDescription()
    {
        return 'This script should fix column naming in order to be compatible with Postgres';
    }

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
        ];
    }

    protected function showTime()
    {
        return true;
    }

    private function init()
    {
        if ($this->hasOption('migrate') && $this->getOption('migrate')) {
            $this->migrate = true;
        }
        if ($this->hasOption('cleanup') && $this->getOption('cleanup')) {
            $this->cleanup = true;
        }
        if (!$this->migrate && !$this->cleanup) {
            throw new \InvalidArgumentException('Run ' . __CLASS__ . ' --help to learn how to use tool');
        }
    }
}
