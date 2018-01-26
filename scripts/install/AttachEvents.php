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

use oat\generis\model\data\event\ResourceCreated;
use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoSync\model\listener\ListenerService;

/**
 * Class RegisterListenerService
 *
 * Register the sync listener service and attach events needed for synchronsiation
 *
 * @package oat\taoSync\scripts\install
 */
class AttachEvents extends InstallAction
{
    public function __invoke($params)
    {
        $this->registerEvent(DeliveryCreatedEvent::class, [ListenerService::SERVICE_ID, 'listen']);
        $this->registerEvent(DeliveryUpdatedEvent::class, [ListenerService::SERVICE_ID, 'listen']);
        $this->registerEvent(ResourceCreated::class, [ListenerService::SERVICE_ID, 'listen']);

        return \common_report_Report::createSuccess('SyncService successfully registered.');
    }
}
