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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\Validator;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\virtualMachine\SupportedVmService;
use oat\taoSync\model\Exception\SyncRequestFailedException;

class SyncParamsValidator extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncParamsValidator';

    public function validate(array $parameters)
    {
        if (!isset($parameters['tao_version'])) {
            throw new SyncRequestFailedException('Required parameter "tao_version" is missing.');
        }

        $supportedVmService = $this->getServiceLocator()->get(SupportedVmService::SERVICE_ID);
        $supportedVmVersions = array_column($supportedVmService->getSupportedVmVersions(), 'literal');


        if (!in_array($parameters['tao_version'], $supportedVmVersions)) {
            throw new SyncRequestFailedException('Provided version of TAO VM is not supported by server: ' . $parameters['tao_version']);
        }
    }
}
