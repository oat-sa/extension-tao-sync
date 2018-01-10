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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
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
        $type = TestTakerSynchronizer::SYNC_ID;

        $this->getSyncService()->synchronizeAll();
//        $this->getSyncService()->synchronizeData($type);

        return \common_report_Report::createInfo('Done.');
    }

    /**
     * @return SyncService
     */
    protected function getSyncService()
    {
        return $this->getServiceLocator()->get(SyncService::SERVICE_ID);
    }
}