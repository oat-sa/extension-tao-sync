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
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;
use oat\taoSync\model\DeliveryLog\EnhancedDeliveryLogService;

class InstallEnhancedDeliveryLog extends InstallAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        $persistenceId = 'default';
        $persistence = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);
        $schemaManager = $persistence->getDriver()->getSchemaManager();
        $schema = $schemaManager->createSchema();
        $fromSchema = clone $schema;

        try {
            $table = $schema->getTable(RdsDeliveryLogService::TABLE_NAME);
            if (!$table->hasColumn(EnhancedDeliveryLogService::COLUMN_IS_SYNCED)) {
                $table->addColumn(EnhancedDeliveryLogService::COLUMN_IS_SYNCED, 'integer', ['notnull' => true, 'length' => 1, 'default' => 0]);
                $queries = $persistence->getPlatform()->getMigrateSchemaSql($fromSchema, $schema);
                foreach ($queries as $query) {
                    $persistence->exec($query);
                }
            }
        } catch (SchemaException $e) {
            $this->logDebug($e->getMessage());
            $this->logDebug('Database Schema already up to date.');
        }

        /** @var RdsDeliveryLogService $deliveryLog */
        $deliveryLog = $this->getServiceLocator()->get(RdsDeliveryLogService::SERVICE_ID);

        $deliveryLog->setOption(RdsDeliveryLogService::OPTION_FIELDS, [
            RdsDeliveryLogService::EVENT_ID,
            RdsDeliveryLogService::CREATED_BY,
            RdsDeliveryLogService::DELIVERY_EXECUTION_ID,
            EnhancedDeliveryLogService::COLUMN_IS_SYNCED
        ]);

        $this->getServiceManager()->register(RdsDeliveryLogService::SERVICE_ID, $deliveryLog);

        return \common_report_Report::createSuccess('RdsDeliveryLogService successfully overwritten.');
    }
}
