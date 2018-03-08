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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoSync\scripts\tool\oauth;

use oat\taoOauth\model\user\UserService;
use oat\taoOauth\scripts\tools\GenerateCredentials;

class GenerateOauthCredentials extends GenerateCredentials
{
    /**
     * Generate an oauth consumer and add to it taoSync role
     *
     * @return \common_report_Report
     */
    public function __invoke($params)
    {
        $report = parent::__invoke([]);

        if (in_array('-cmd', $params)) {
            return \common_report_Report::createInfo(
                'php index.php \'' . ImportOauthCredentials::class . '\'' .
                ' -k ' . $this->key .
                ' -s ' . $this->secret .
                ' -tu ' . $this->tokenUrl .
                ' -u ' . ROOT_URL
            );
        }

        return $report;
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->getServiceLocator()->get(UserService::SERVICE_ID);
    }
}