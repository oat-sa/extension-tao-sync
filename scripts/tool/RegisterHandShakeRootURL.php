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
namespace oat\taoSync\scripts\tool;


use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\User\HandShakeService;

/**
 * Class RegisterHandShakeRootURL
 * @package oat\taoSync\scripts\tool
 *
 * sudo -u www-data php index.php '\oat\taoSync\scripts\tool\RegisterHandShakeRootURL' --rootUrl=http://tao.dev/
 */
class RegisterHandShakeRootURL extends InstallAction
{
    public function __invoke($params)
    {
        foreach ($params as $param) {
            list($option, $value) = explode('=', $param);

            switch ($option) {
                case '--rootUrl':
                   $rootUrl = $value;
                break;
            }
        }

        if (!isset($rootUrl)){
            throw new \Exception('Please specify the --rootUrl=...');
        }

        /** @var HandShakeService $service */
        $service = $this->getServiceManager()->get(HandShakeService::SERVICE_ID);
        $service->setOption(HandShakeService::OPTION_ROOT_URL, $rootUrl);
        $service->setOption(HandShakeService::OPTION_REMOTE_AUTH_URL, $rootUrl . 'taoSync/HandShake');

        $this->registerService(HandShakeService::SERVICE_ID, $service);

        return \common_report_Report::createSuccess('HandShakeService root url successfully registered.');
    }
}