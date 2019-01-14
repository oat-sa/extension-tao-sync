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

namespace oat\taoSync\model\SyncLog\Storage;

use oat\taoSync\model\SyncLog\SyncLogEntity;
use oat\taoSync\model\SyncLog\SyncLogFilter;

/**
 * Interface SyncLogStorageInterface
 * @package oat\taoSync\model\SyncLog\Storage
 */
interface SyncLogStorageInterface
{
    const COLUMN_ID = 'id';
    const COLUMN_BOX_ID = 'box_id';
    const COLUMN_SYNC_ID = 'sync_id';
    const COLUMN_ORGANIZATION_ID = 'organization_id';
    const COLUMN_DATA = 'data';
    const COLUMN_STATUS = 'status';
    const COLUMN_REPORT = 'report';
    const COLUMN_STARTED_AT = 'created_at';
    const COLUMN_FINISHED_AT = 'finished_at';

    /**
     * Store synchronization log record.
     *
     * @param SyncLogEntity $entity
     * @return integer Id of created record.
     */
    public function create(SyncLogEntity $entity);

    /**
     * Update synchronization log record.
     *
     * @param SyncLogEntity $entity
     * @return integer Number of updated records.
     */
    public function update(SyncLogEntity $entity);

    /**
     * Get synchronization log record by id.
     *
     * @param integer $id
     * @return SyncLogEntity
     */
    public function getById($id);

    /**
     * Get synchronization log record by synchronization ID and client ID.
     *
     * @param integer $syncId
     * @param string $boxId
     * @return SyncLogEntity
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
     * @param SyncLogFilter $syncLogFilter
     * @return array
     */
    public function search(SyncLogFilter $syncLogFilter);
}
