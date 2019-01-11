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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\SyncLog;

use common_exception_NotFound;
use common_exception_Error;
use oat\taoSync\model\Exception\SyncLogEntityNotFound;

/**
 * Interface SyncLogServiceInterface
 * @package oat\taoSync\model\SyncLog
 */
interface SyncLogServiceInterface
{
    const SERVICE_ID = 'taoSync/SyncLogService';

    const PARAM_SYNC_ID = 'sync_id';
    const PARAM_BOX_ID = 'box_id';
    const PARAM_ORGANIZATION_ID = 'organisation_id';

    /**
     * Create new synchronization log record.
     *
     * @param SyncLogEntity $entity
     * @return integer Created record ID
     */
    public function create(SyncLogEntity $entity);

    /**
     * Update existing synchronization log record.
     *
     * @param SyncLogEntity $entity
     * @return integer Number of updated records.
     */
    public function update(SyncLogEntity $entity);

    /**
     * Get SyncLogEntity by ID.
     *
     * @param integer $id
     * @return SyncLogEntity
     *
     * @throws SyncLogEntityNotFound
     * @throws common_exception_Error
     */
    public function getById($id);

    /**
     * Get SyncLogEntity by synchronization ID and box ID.
     *
     * @param integer $syncId
     * @param string $boxId
     * @return SyncLogEntity
     *
     * @throws SyncLogEntityNotFound
     * @throws common_exception_Error
     */
    public function getBySyncIdAndBoxId($syncId, $boxId);

    /**
     * Get total amount of synchronization logs by provided filters.
     *
     * @param SyncLogFilter $filter
     * @return integer
     */
    public function count(SyncLogFilter $filter);

    /**
     * Search synchronization logs by provided filters.
     *
     * @param SyncLogFilter $filter
     * @return array
     */
    public function search(SyncLogFilter $filter);
}
