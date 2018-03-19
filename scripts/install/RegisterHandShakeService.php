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
use oat\taoSync\model\User\HandShakeService;

/**
 * sudo -u www-data php index.php 'oat\taoSync\scripts\install\RegisterHandShakeService'
 */
class RegisterHandShakeService extends InstallAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        $handShake = new HandShakeService([
            HandShakeService::OPTION_REMOTE_AUTH_URL => 'http://tao.dev/taoSync/handShake'
        ]);

        $this->registerService(HandShakeService::SERVICE_ID, $handShake);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'HandShakeService was registered.');
    }
}