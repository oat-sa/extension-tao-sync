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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\taoSync\model;

use common_Exception;
use common_persistence_KeyValuePersistence;
use common_persistence_Manager as PersistenceManager;
use InvalidArgumentException;
use oat\generis\model\data\event\ResourceDeleted;
use oat\oatbox\service\ConfigurableService;

class EntityChecksumCacheService extends ConfigurableService
{
    public const SERVICE_ID = 'taoSync/EntityChecksumCacheService';
    public const OPTION_PERSISTENCE = 'persistence';

    private const PREFIX = 'entity-checksum-';

    /**
     * @param string $id
     * @return null|string
     */
    public function get(string $id): ?string
    {
        if (empty($id)) {
            return null;
        }
        $result =  $this->getPersistence()->get($this->makeKey($id));

        return !empty($result) ? $result : null;
    }

    /**
     * @param string $id
     * @param string $checksum
     * @return bool
     * @throws common_Exception
     */
    public function set(string $id, string $checksum): bool
    {
        if (empty($id)) {
            return false;
        }

        return $this->getPersistence()->set($this->makeKey($id), $checksum);
    }

    /**
     * @param string $id
     */
    public function delete(string $id): void
    {
        if (empty($id)) {
            return;
        }

        $this->getPersistence()->del($this->makeKey($id));
    }

    /**
     * @param ResourceDeleted $event
     */
    public function entityDeleted(ResourceDeleted $event): void
    {
        $this->delete($event->getId());
    }

    private function makeKey(string $id): string
    {
        return self::PREFIX . $id;
    }

    /**
     * @return common_persistence_KeyValuePersistence
     */
    private function getPersistence()
    {
        if (!$this->hasOption(self::OPTION_PERSISTENCE)) {
            throw new InvalidArgumentException('Persistence for ' . self::SERVICE_ID . ' is not configured');
        }

        $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);

        return $this->getServiceLocator()->get(PersistenceManager::SERVICE_ID)->getPersistenceById($persistenceId);
    }
}
