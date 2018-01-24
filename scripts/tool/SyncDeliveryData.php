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

use oat\oatbox\extension\AbstractAction;
use oat\taoSync\model\synchronizer\testtaker\TestTakerSynchronizer;
use oat\taoSync\model\SyncService;

class SyncDeliveryData extends AbstractAction
{
    public function __invoke($params)
    {
        $this->checkToken();
        $token = $this->getOrgIdFromToken();
        $type = $orgId = null;

        foreach ($params as $param) {
            list($option, $value) = explode('=', $param);

            switch ($option) {
                case '--orgId':
                    $orgId = $value;
                    break;

                case '--type':
                    $type = $value;
                    break;
            }
        }

        if (is_null($orgId)) {
            throw new \common_exception_RestApi('Expected "--orgId" argument is missing.');
        }

        $options = [
            'orgId' => $orgId
        ];

        return $this->getSyncService()->synchronize($type, $options);
    }

    protected function getOrgIdFromToken()
    {
        return '123456';
    }

    protected function checkToken()
    {
        return true;
    }
    /**
     * @return SyncService
     */
    protected function getSyncService()
    {
        return $this->getServiceLocator()->get(SyncService::SERVICE_ID);
    }
}