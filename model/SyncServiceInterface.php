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
 * Interface SyncServiceInterface
 * @package oat\taoSync\model
 */
interface SyncServiceInterface
{

    const IMPORT_OPTION_BOX_ID = 'box_id';
    const BOX_ID_HEADER = 'x-tao-box-id';

    /**
     * Save the box id of the Tao instance the data came from.
     * @param array $data Data given from client environment
     * @param string $boxId
     * @return boolean
     */
    public function saveBoxId($data, $boxId);
}
