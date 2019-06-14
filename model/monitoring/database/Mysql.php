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

class Mysql extends DatabaseSpaceUsageService
{
    /**
     * @inheritDoc
     * @throws InvalidServiceManagerException
     */
    protected function getTargetVolume()
    {
        $data = $this->getPersistence()->query('SHOW VARIABLES WHERE Variable_Name = "datadir";')->fetch();
        return $data['Value'];
    }

    /**
     * @inheritDoc
     * @throws InvalidServiceManagerException
     */
    protected function getSpaceUsage()
    {
        $qb = $this->getPersistence()->getPlatform()->getQueryBuilder();
        $data = $qb->select('SUM(data_length + index_length) AS size')
            ->from('information_schema.TABLES')
            ->where('table_schema = :dbName')
            ->setParameter('dbName', $qb->getConnection()->getDatabase())
            ->execute()->fetch();

        return $data['size'];
    }
}
