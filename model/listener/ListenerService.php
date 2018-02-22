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

namespace oat\taoSync\model\listener;

use oat\generis\model\data\event\ResourceCreated;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoSync\model\Entity;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizerService;

/**
 * Class ListenerService
 *
 * Service to listen events to prepare synchronisation
 *
 * @package oat\taoSync\model\listener
 */
class ListenerService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/listenerService';

    /**
     * Generic method to wrap an event listener to a method
     *
     * @param Event $event
     * @return \common_report_Report
     */
    public static function listen(Event $event)
    {
        $listenerService = ServiceManager::getServiceManager()->get(self::SERVICE_ID);
        $eventName = (new \ReflectionClass($event))->getShortName();
        $listener = 'on' . $eventName;
        if (method_exists($listenerService, $listener)) {
            ServiceManager::getServiceManager()->propagate($listenerService);
            return $listenerService->$listener($event);
        }
        return \common_report_Report::createInfo(__CLASS__ . ' does not handle the event "' . $event->getName() . '".');

    }

    /**
     * Create a test package backup when a delivery is created
     *
     * @param DeliveryCreatedEvent $event
     * @throws \common_Exception
     */
    public function onDeliveryCreatedEvent(DeliveryCreatedEvent $event)
    {
        return $this->getDeliverySyncService()->backupDeliveryTest($this->getResource($event->getDeliveryUri()));
    }

    /**
     * Create a test package backup when a delivery is updated
     *
     * The package is created only if it does not exist.
     *
     * @param DeliveryUpdatedEvent $event
     * @return \common_report_Report
     * @throws \common_Exception
     */
    public function onDeliveryUpdatedEvent(DeliveryUpdatedEvent $event)
    {
        return $this->getDeliverySyncService()->backupDeliveryTest($this->getResource($event->getDeliveryUri()));
    }

    /**
     * Resource created listener
     *
     * Create the property CreatedAt at resource creation
     *
     * @param ResourceCreated $event
     */
    public function onResourceCreated(ResourceCreated $event)
    {
        $resource = $event->getResource();
        $createdAtProperty = $this->getProperty(Entity::CREATED_AT);
        $resource->setPropertyValue($createdAtProperty, $this->getNowExpression());
    }

    /**
     * Get the current time in milliseconds
     *
     * @return float
     */
    protected function getNowExpression()
    {
        return microtime(true);
    }

    /**
     * Get the service to synchronize delivery tests
     *
     * @return DeliverySynchronizerService
     */
    protected function getDeliverySyncService()
    {
        return $this->getServiceLocator()->get(DeliverySynchronizerService::SERVICE_ID);
    }

}