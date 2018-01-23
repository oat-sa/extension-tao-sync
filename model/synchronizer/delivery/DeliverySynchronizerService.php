<?php

namespace oat\taoSync\model\synchronizer\delivery;

use oat\generis\model\fileReference\UrlFileSerializer;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\import\ImportersService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoQtiTest\models\import\QtiTestImporter;
use oat\taoSync\model\api\SynchronisationClient;
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

    const DELIVERY_TEST_PACKAGE_URI = 'http://www.taotesting.com/ontologies/synchro.rdf#OriginTestPackage';

    /**
     * Import and compile a test to an existing delivery from a remote environment
     *
     * @param $id
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     */
    public function synchronizeDelivery($id)
    {
        $delivery = $this->getResource($id);

        $deliveryClass = $this->getClass($delivery->getOnePropertyValue($this->getProperty(OntologyRdf::RDF_TYPE)));
        $test = $this->importRemoteDeliveryTest($delivery);

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
     * @throws \core_kernel_persistence_Exception
     * @throws \oat\generis\model\fileReference\FileSerializerException
     */
    public function getDeliveryTestPackage($id)
    {
        $delivery = $this->getResource($id);
        if (!$delivery->exists()) {
            throw new \common_exception_NotFound('Delivery ' . $id . ' does not exist.');
        }
        /** @var DeliverySynchronizer $deliverySynchronizer */
        $deliverySynchronizer = $this->getSyncService()->getSynchronizer('delivery');
        $formattedDelivery = $deliverySynchronizer->format($id);

        if (!isset($formattedDelivery['properties'][DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI])) {
            $formattedDelivery['properties'][DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI] =
                $delivery->getOnePropertyValue($this->getProperty(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI));
        }
        $serial = $formattedDelivery['properties'][DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI];
        /** @var File $file */
        $file = $this->getFileSerializer()->unserialize($serial);
        $formattedDelivery['testPackage'] = $file;

        return $formattedDelivery;
    }

    /**
     * Create a test package from delivery test origin
     *
     * @param \core_kernel_classes_Resource $delivery
     * @throws \League\Flysystem\FileExistsException
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     */
    public function backupDeliveryTest(\core_kernel_classes_Resource $delivery)
    {
        if (is_null($delivery->getOnePropertyValue($this->getProperty(self::DELIVERY_TEST_PACKAGE_URI)))) {
            return;
        }

        /** @var \core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN));
        if (!$test->exists()) {
            return;
        }

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
        $client = $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
        $testPackage = $client->getRemoteDeliveryTest($delivery->getUri());

        /** @var FileSystemService $filesytem */
        $filesytem = $this->getServiceLocator()->get(FileSystemService::SERVICE_ID);
        $file = $filesytem->getDirectory('sharedTmp')->getFile('import/' . \tao_helpers_Uri::getUniqueId($delivery->getUri()) . '/testPackage.zip');
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