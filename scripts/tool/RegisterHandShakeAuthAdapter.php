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

use common_ext_ExtensionsManager;
use oat\generis\model\user\AuthAdapter;
use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\User\HandShakeAuthAdapter;

/**
 * Class RegisterHandShakeAuthAdapter
 * @package oat\taoSync\scripts\tool\synchronisation
 * sudo -u www-data php index.php '\oat\taoSync\scripts\tool\RegisterHandShakeAuthAdapter'
 */
class RegisterHandShakeAuthAdapter extends InstallAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_exception_Error
     * @throws \common_ext_ExtensionException
     * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
     */
    public function __invoke($params)
    {
        /** @var common_ext_ExtensionsManager $extensionManager */
        $extensionManager = $this->getServiceManager()->get(common_ext_ExtensionsManager::SERVICE_ID);
        $config = $extensionManager ->getExtensionById('generis')->getConfig('auth');

        foreach ($config as $index => $adapter) {
            if ($adapter['driver'] === AuthAdapter::class) {
                $adapter['driver'] = HandShakeAuthAdapter::class;
            }
            $config[$index] = $adapter;
        }

        $extensionManager->getExtensionById('generis')->setConfig('auth', array_values($config));

        return \common_report_Report::createSuccess('RegisterHandShakeAuthAdapter successfully registered.');
    }
}
