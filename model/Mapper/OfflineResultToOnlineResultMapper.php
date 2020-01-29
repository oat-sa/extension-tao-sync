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

namespace oat\taoSync\model\Mapper;

use common_persistence_KeyValuePersistence;
use oat\oatbox\service\ConfigurableService;

class OfflineResultToOnlineResultMapper extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/OfflineToOnlineResultMapper';

    const OPTION_PERSISTENCE = 'persistence';

    const PREFIX_MAPPER = 'mapOfflineResultToOnlineResult_';

    /** @var common_persistence_KeyValuePersistence */
    private $persistence;

    /**
     * @throws \Exception
     * @return common_persistence_KeyValuePersistence
     */
    protected function getPersistence()
    {
        if (is_null($this->persistence)) {
            $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);
            $persistence = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);

            if (!$persistence instanceof common_persistence_KeyValuePersistence) {
                throw new \Exception('Only common_persistence_KeyValuePersistence supported');
            }

            $this->persistence = $persistence;
        }

        return $this->persistence;
    }

    /**
     * @param string $offlineResultId
     * @param string $onlineResultId
     * @return bool
     * @throws \common_Exception
     * @throws \Exception
     */
    public function set($offlineResultId, $onlineResultId)
    {
        return $this->getPersistence()->set(self::PREFIX_MAPPER . $offlineResultId, $onlineResultId);
    }

    /**
     * @param string $offlineResultId
     * @return bool|int|null|string
     * @throws \Exception
     */
    public function getOnlineResultId($offlineResultId)
    {
        return $this->getPersistence()->get(self::PREFIX_MAPPER . $offlineResultId);
    }
}
