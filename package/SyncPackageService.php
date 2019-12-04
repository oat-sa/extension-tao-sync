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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 * @author Yuri Filippovich
 */

namespace oat\taoSync\package;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\package\storage\StorageInterface;

class SyncPackageService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncPackageService';
    const OPTION_STORAGE = 'storage';

    /**
     * @var $params
     */
    private $storage;

    /**
     * @param array $data
     * @param int $packageName
     * @return bool
     */
    public function createPackage(array $data, $packageName)
    {
      return $this->getStorage()->createPackage($data, $packageName);
    }

    /**
     * @return StorageInterface
     */
    private function getStorage()
    {
        if (!$this->storage) {
            $this->storage = $this->propagate($this->getOption(self::OPTION_STORAGE));
        }
        return $this->storage;
    }
}
