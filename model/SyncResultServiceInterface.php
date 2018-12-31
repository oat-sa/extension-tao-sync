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
namespace oat\taoSync\model;


/**
 * Class SyncService
 * @package oat\taoSync\model
 */
interface SyncResultServiceInterface
{
    const SYNC_ENTITY = 'delivery execution';

    /**
     * Scan delivery execution to format it
     *
     * Send results to remote server by configured chunk
     * Send only finished delivery execution
     * Do not resend already sent delivery execution
     * Log result has been sent into ResultHistoryService
     *
     * @param array $params
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     */
    public function synchronizeResults(array $params = []);

    /**
     * Send results to remote server and process acknowledgment
     *
     * Delete results following configuration
     *
     * @param $results
     * @param array $params Synchronization parameters
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function sendResults($results, array $params);

    /**
     * Import delivery by scanning $results
     *
     * Spawn a delivery execution with delivery and test-taker
     * Create and inject variables
     *
     * @param array $results
     * @param array $params Synchronization parameters
     * @return array
     */
    public function importDeliveryResults(array $results, array $params = []);

    /**
     * @param string $offlineResultId
     * @param string $onlineResultId
     * @return boolean
     */
    public function mapOfflineResultIdToOnlineResultId($offlineResultId, $onlineResultId);
}