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

namespace oat\taoSync\model\synchronizer;

interface Synchronizer
{
    /**
     * Get the synchronizer identifier
     *
     * @return string
     */
    public function getId();

    /**
     * This method is call before to write the data to synchronize
     *
     * @param array $entities
     */
    public function before(array $entities);

    /**
     * Get a list of instances
     *
     * @param array $options array of query options
     * @return array
     */
    public function fetch(array $options = []);

    /**
     * Fetch an entity associated to the given id in Rdf storage
     *
     * @param $id
     * @return array
     * @throws \common_exception_NotFound If entity is not found
     */
    public function fetchOne($id);

    /**
     * Return count of instances
     *
     * @return int
     */
    public function count();

    /**
     * Delete multiple entities
     *
     * @param array $entityIds Array of entity id
     */
    public function deleteMultiple(array $entityIds);

    /**
     * Insert multiple entities
     *
     * @param array $entities
     */
    public function insertMultiple(array $entities);

    /**
     * Update multiple entities
     *
     * @param array $entities
     */
    public function updateMultiple(array $entities);
}