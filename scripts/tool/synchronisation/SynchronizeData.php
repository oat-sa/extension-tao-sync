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

namespace oat\taoSync\scripts\tool\synchronisation;

use oat\oatbox\extension\AbstractAction;
use oat\taoSync\model\SyncService;

class SynchronizeData extends AbstractAction
{
    public function __invoke($params)
    {
        $type = null;

        foreach ($params as $key => $param) {
            if (strpos($param, '=') !== false) {
                list($option, $value) = explode('=', $param);
            } else {
                $option = $key;
                $value = $param;
            }

            switch ($option) {
                case '--type':
                    $type = $value;
                    break;
            }
        }

        return $this->getSyncService()->synchronize($type);
    }

    /**
     * @return SyncService
     */
    protected function getSyncService()
    {
        return $this->getServiceLocator()->get(SyncService::SERVICE_ID);
    }
}