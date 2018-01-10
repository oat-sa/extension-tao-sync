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
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\scripts\tool\SyncDeliveryData;

class RegisterSyncPublishingAction extends InstallAction
{
    public function __invoke($params)
    {
        $service = $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
        $actions = $service->getOption(PublishingService::OPTIONS_ACTIONS);
        if (!in_array(SyncDeliveryData::class, $actions)) {
            $actions[] = SyncDeliveryData::class;
            $service->setOption(PublishingService::OPTIONS_ACTIONS, $actions);
            $this->registerService(PublishingService::SERVICE_ID, $service);
        }

        return \common_report_Report::createSuccess('Publishing service successfully updated.');
    }

}