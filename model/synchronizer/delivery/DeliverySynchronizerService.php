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

namespace oat\taoSync\model\synchronizer\delivery;

use oat\generis\model\fileReference\UrlFileSerializer;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\import\ImportersService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoQtiTest\models\import\QtiTestImporter;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\SyncService;

/**
 * Class DeliverySynchronizerService
 *
 * Service to manage the delivery content.
 * Able to import a remote test and compile it to a delivery
 *
 * @package oat\taoSync\model\synchronizer\delivery
 */
class DeliverySynchronizerService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/deliverySyncService';

    const DELIVERY_TEST_PACKAGE_URI = 'http://www.taotesting.com/ontologies/synchro.rdf#OriginTestPackage';

    /**
     * Import and compile a test to an existing delivery from a remote environment
     *
     * @param $id
     * @return \common_report_Report
     */
    public function synchronizeDelivery($id)
    {
        try {
            $delivery = $this->getResource($id);
            $test = $this->importRemoteDeliveryTest($delivery);
            $deliveryClass = $this->getClass($delivery->getOnePropertyValue($this->getProperty(OntologyRdf::RDF_TYPE)));
        } catch (\common_Exception $e) {
            return null;
        }

        /** @var DeliveryFactory $deliveryFactory */
        $deliveryFactory = $this->getServiceLocator()->get(DeliveryFactory::SERVICE_ID);
        return $deliveryFactory->create($deliveryClass, $test, $delivery->getLabel(), $delivery);
    }

    /**
     * Get a delivery package associated to the given id
     * A delivery package is composed by a formatted delivery with the test package file
     *
     * @param $id
     * @return array
     * @throws \common_exception_BadRequest
     * @throws \common_exception_NotFound
     */
    public function getDeliveryTestPackage($id)
    {
        $delivery = $this->getResource($id);
        if (!$delivery->exists()) {
            throw new \common_exception_NotFound('Delivery ' . $id . ' does not exist.');
        }
        /** @var RdfDeliverySynchronizer $deliverySynchronizer */
        $deliverySynchronizer = $this->getSyncService()->getSynchronizer('delivery');
        $formattedDelivery = $deliverySynchronizer->format($id);

        try {
            $testPackageSerial = $delivery->getOnePropertyValue($this->getProperty(self::DELIVERY_TEST_PACKAGE_URI));
            if (is_null($testPackageSerial)) {
                throw new \common_exception_NotFound('Delivery ' . $id . ' does not have an associated test package backup.');
            }
            /** @var File $file */
            $file = $this->getFileSerializer()->unserialize($testPackageSerial);
            $formattedDelivery['testPackage'] = $file;
            $formattedDelivery['properties'][self::DELIVERY_TEST_PACKAGE_URI] = $testPackageSerial;
        } catch (\Exception $e) {
            throw new \common_exception_NotFound('Delivery ' . $id . ' is linked to a non valid testPackage', 0, $e);
        }

        return $formattedDelivery;
    }

    /**
     * Create a test package from delivery test origin to synchronisation filesystem
     *
     * @param \core_kernel_classes_Resource $delivery
     * @throws \common_Exception
     */
    public function backupDeliveryTest(\core_kernel_classes_Resource $delivery)
    {
        $testPackageSerial = $delivery->getOnePropertyValue($this->getProperty(self::DELIVERY_TEST_PACKAGE_URI));
        if (!is_null($testPackageSerial)) {
            return;
        }

        /** @var \core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN));
        if (!$test->exists()) {
            return;
        }

        try {
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

            $serial = $this->getFileSerializer()->serialize($file);
            $delivery->setPropertyValue($this->getProperty(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI), $serial);
        } catch (\Exception $e) {
            throw new \common_Exception('An error has occurred during test package backup : ' . $e->getMessage(), 0, $e);
        }

    }

    /**
     * Fetch and import a remote test to specified delivery
     *
     * @param \core_kernel_classes_Resource $delivery
     * @return \core_kernel_classes_Resource The imported test
     * @throws \common_Exception
     */
    protected function importRemoteDeliveryTest(\core_kernel_classes_Resource $delivery)
    {
        if (!$delivery->exists()) {
            throw new \common_Exception('Delivery does not exist. Test import cancelled');
        }

        try {
            $testPackageSerial = $delivery->getOnePropertyValue($this->getProperty(self::DELIVERY_TEST_PACKAGE_URI));
            $file = $this->getFileSerializer()->unserializeFile($testPackageSerial);
            if ($file->exists()) {
                $file->delete();
            }
        } catch (\common_Exception $e) {
            \common_Logger::d('Problem to fetch test backup. Replace it by import.');
            $file = $this->getServiceLocator()
                ->get(FileSystemService::SERVICE_ID)
                ->getDirectory('synchronisation')
                ->getFile(\tao_helpers_Uri::getUniqueId($delivery->getUri()) . DIRECTORY_SEPARATOR . 'export.zip');
        }

        $client = $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
        $testPackage = $client->getRemoteDeliveryTest($delivery->getUri());

        $file->put($testPackage);

        /** @var QtiTestImporter $importer */
        $importer = $this->getServiceLocator()->get(ImportersService::SERVICE_ID)->getImporter('taoQtiTest');
        $report = $importer->import($file);

        if ($report->getType() == \common_report_Report::TYPE_SUCCESS) {
            foreach ($report as $r) {
                return $r->getData()->rdfsResource;
            }
        } else {
            throw new \common_Exception($file->getBasename() . 'Unable to import test with message '. $report->getMessage());
        }
    }

    /**
     * Get sync service
     *
     * @return SyncService
     */
    protected function getSyncService()
    {
        return $this->getServiceLocator()->get(SyncService::SERVICE_ID);
    }

    /**
     * Get file serializer
     *
     * @return UrlFileSerializer
     */
    protected function getFileSerializer()
    {
        return $this->propagate(new UrlFileSerializer());
    }
}