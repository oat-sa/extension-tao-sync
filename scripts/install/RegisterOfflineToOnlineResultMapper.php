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

namespace oat\taoSync\scripts\install;

use oat\oatbox\extension\InstallAction;
use common_report_Report as Report;
use oat\taoSync\model\Mapper\OfflineResultToOnlineResultMapper;

class RegisterOfflineToOnlineResultMapper extends InstallAction
{
    /**
     * @param $params
     * @return Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        $report = Report::createSuccess();

        $persistenceId = 'mapOfflineToOnlineResultIds';

        try {
            \common_persistence_Manager::getPersistence($persistenceId);
        } catch (\common_Exception $e) {
            \common_persistence_Manager::addPersistence($persistenceId, [
                'driver' => 'SqlKvWrapper',
                'sqlPersistence' => 'default',
            ]);
        }

        $mapper = new OfflineResultToOnlineResultMapper([
            OfflineResultToOnlineResultMapper::OPTION_PERSISTENCE => 'mapOfflineToOnlineResultIds'
        ]);

        $this->getServiceManager()->register(OfflineResultToOnlineResultMapper::SERVICE_ID, $mapper);

        return $report;
    }
}
