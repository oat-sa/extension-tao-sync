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

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\event\Event;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoRevision\model\Repository;
use oat\taoRevision\model\RepositoryService;

class ListenerService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/listenerService';

    public static function listen(Event $event)
    {
        $listenerService = ServiceManager::getServiceManager()->get(self::SERVICE_ID);
        $shortName = (new \ReflectionClass($event))->getShortName();
        $listener = 'on' . ucfirst($shortName);
        if (method_exists($listenerService, $listener)) {
            ServiceManager::getServiceManager()->propagate($listenerService);
            return $listenerService->$listener($event);
        }
        return \common_report_Report::createInfo('ListenerService for synchronisation does not handle the event "' . $event->getName() . '".');

    }

    public function onDeliveryCreatedEvent(DeliveryCreatedEvent $event)
    {
        $delivery = $this->getResource($event->getDeliveryUri());
        /** @var \core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN));
        if ($test->exists()) {
            /** @var RepositoryService $revisionRepository */
            $revisionRepository = $this->getServiceLocator()->get(Repository::SERVICE_ID);
            $revision = $revisionRepository->commit($test->getUri(), __('Test origin for delivery %s', $delivery->getUri()));
            $delivery->setPropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#OriginTestIdRevision'), $revision->getResourceId());
            $delivery->setPropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#OriginTestVersionRevision'), $revision->getVersion());
        }
        $report = \common_report_Report::createSuccess();

        return $report;
    }

    public function onDeliveryUpdatedEvent(DeliveryUpdatedEvent $event)
    {
        $delivery = $this->getResource($event->getDeliveryUri());
        /** @var \core_kernel_classes_Resource $test */
        if (is_null($delivery->getOnePropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#TestOriginRevision')))) {
            $test = $delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN));
            if ($test->exists()) {
                /** @var RepositoryService $revisionRepository */
                $revisionRepository = $this->getServiceLocator()->get(Repository::SERVICE_ID);
                $revision = $revisionRepository->commit($test->getUri(), __('Test origin for delivery %s', $delivery->getUri()));
                $delivery->setPropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#OriginTestIdRevision'), $revision->getResourceId());
                $delivery->setPropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#OriginTestVersionRevision'), $revision->getVersion());
            }
        }

        $report = \common_report_Report::createSuccess();

        return $report;
    }
}