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

use oat\generis\model\fileReference\UrlFileSerializer;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\event\Event;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
<<<<<<< Updated upstream
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizerService;
=======
>>>>>>> Stashed changes

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
        $eventName = $event->getName();
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
     * @return \common_report_Report
     */
    public function onDeliveryCreatedEvent(DeliveryCreatedEvent $event)
    {
<<<<<<< Updated upstream
        return $this->getDeliverySyncService()->backupDeliveryTest($this->getResource($event->getDeliveryUri()));
    }

    /**
     * Create a test package backup when a delivery is updated
     *
     * The package is created only if it does not exist.
     *
     * @param DeliveryUpdatedEvent $event
     * @return \common_report_Report
     */
    public function onDeliveryUpdatedEvent(DeliveryUpdatedEvent $event)
    {
        return $this->getDeliverySyncService()->backupDeliveryTest($this->getResource($event->getDeliveryUri()));
    }

    /**
     * Get the service to synchronize delivery tests
     *
     * @return DeliverySynchronizerService
     */
    protected function getDeliverySyncService()
    {
        return $this->getServiceLocator()->get(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI);
=======
        return $this->backupDeliveryTest($this->getResource($event->getDeliveryUri()));
    }

    public function onDeliveryUpdatedEvent(DeliveryUpdatedEvent $event)
    {
        $delivery = $this->getResource($event->getDeliveryUri());
        /** @var \core_kernel_classes_Resource $test */
        if (is_null($delivery->getOnePropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#OriginTestIdRevision')))) {
            $this->backupDeliveryTest($delivery);
        }

        $report = \common_report_Report::createSuccess();

        return $report;
    }

    protected function backupDeliveryTest(\core_kernel_classes_Resource $delivery)
    {
        /** @var \core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN));
        if ($test->exists()) {

            $exportDir = \tao_helpers_File::createTempDir();
            $exportFile = 'export.zip';
            $exporter = new \taoQtiTest_models_classes_export_TestExport();
            $report = $exporter->export(
                array(
                    'filename' => $exportFile,
                    'instances' => $test->getUri()
                ),
                $exportDir
            );
            \common_Logger::d('Exporting Test '.$test->getUri().' to synchronisation dir: ' . $report->getData());
            $source = fopen($report->getData(), 'r');

            /** @var File $file */
            $file = $this->getServiceLocator()
                ->get(FileSystemService::SERVICE_ID)
                ->getDirectory('synchronisation')
                ->getFile(\tao_helpers_Uri::getUniqueId($delivery->getUri()) . DIRECTORY_SEPARATOR . 'export.zip');

            $file->write($source);
            fclose($source);
            @unlink($exportFile);
            @rmdir($exportDir);

            $serializer = $this->propagate(new UrlFileSerializer());
            $serial = $serializer->serialize($file);
            $delivery->setPropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#OriginTestIdRevision'), $serial);
        }
    }

    protected function restoreDeliveryTest()
    {

>>>>>>> Stashed changes
    }

}