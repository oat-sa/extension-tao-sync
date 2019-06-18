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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */
namespace oat\taoSync\model\monitoring\database;

use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoSync\model\monitoring\DatabaseSpaceUsageService;

class Postgresql extends DatabaseSpaceUsageService
{
    /**
     * @inheritDoc
     * @throws InvalidServiceManagerException
     */
    protected function getTargetVolume()
    {
        $data = $this->getPersistence()->query('show data_directory;')->fetch();
        return $data['data_directory'];
    }

    /**
     * @inheritDoc
     * @throws InvalidServiceManagerException
     */
    protected function getSpaceUsage()
    {
        $data = $this->getPersistence()->getPlatform()->getQueryBuilder()
            ->select('sum(pg_database_size(pg_database.datname))')
            ->from('pg_database')->execute()->fetch();

        return $data['sum'];
    }
}
