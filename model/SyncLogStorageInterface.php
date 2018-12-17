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

namespace oat\taoSync\model;

use oat\taoSync\model\SyncLog\SyncLogEntity;

/**
 * Class StorageInterface
 * @package oat\taoSync\model
 */
interface SyncLogStorageInterface
{
    /**
     * Store synchronization log record.
     *
     * @param SyncLogEntity $entity
     * @return mixed
     */
    public function create(SyncLogEntity $entity);

    /**
     * Update synchronization log record.
     *
     * @param SyncLogEntity $entity
     * @return mixed
     */
    public function update(SyncLogEntity $entity);

    /**
     * Get synchronization log record by id.
     *
     * @param $id
     * @return SyncLogEntity
     */
    public function getById($id);

    /**
     * Get synchronization log record by synchronization ID and client ID.
     *
     * @param $syncId
     * @param $clientId
     * @return SyncLogEntity
     */
    public function getBySyncIdAndClientId($syncId, $clientId);
}
