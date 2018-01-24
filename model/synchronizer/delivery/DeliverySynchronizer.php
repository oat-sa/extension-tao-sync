<?php

namespace oat\taoSync\model\synchronizer\delivery;

use oat\generis\model\fileReference\UrlFileSerializer;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoSync\model\api\SynchronisationClient;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoSync\model\synchronizer\Synchronizer;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

class DeliverySynchronizer extends AbstractResourceSynchronizer implements Synchronizer
{
    /** @var \core_kernel_classes_Resource[] */
    protected $testToImport = [];

    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $fields = $this->getOption(self::OPTIONS_FIELDS);
        $excludedFields = $this->getOption(self::OPTIONS_EXCLUDED_FIELDS);
        if (!empty($fields) && !in_array(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI, $fields)) {
            $fields[] = DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI;
            $this->setOption(self::OPTIONS_FIELDS, $fields);
        } elseif (!empty($excludedFields) && in_array(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI, $excludedFields)) {
            unset($excludedFields[array_search(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI, $excludedFields)]);
            $this->setOption(self::OPTIONS_EXCLUDED_FIELDS, $excludedFields);
        }
    }


    protected function getRootClass()
    {
        return $this->getClass(DeliveryAssemblyService::CLASS_URI);
    }

    public function getId()
    {
        return 'delivery';
    }

    public function fetch(array $options = [])
    {
        $id = $this->getOrganisationIdFromOption($options);
        /**
         * @todo apply options
         */

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, EligibilityService::CLASS_URI, true);
        $queryBuilder->setCriteria($query);

        $queryBuilder2 = $search->query();
        $query2 = $search->searchType($queryBuilder2, TestCenterService::CLASS_URI, true)
            ->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id);
        $queryBuilder2->setCriteria($query2);

        /** @var QueryJoiner $joiner */
        $joiner = $search->getGateway()->getJoiner();
        $joiner->setQuery($queryBuilder)
            ->join($queryBuilder2)
            ->on(EligibilityService::PROPERTY_TESTCENTER_URI);

        $results = $search->getGateway()->join($joiner);
        $values = [];
        if ($results->total() > 0) {
            /** @var \core_kernel_classes_Resource $resource */
            foreach ($results as $resource) {
                $deliveryUris = $resource->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_DELIVERY_URI));
                foreach ($deliveryUris as $deliveryUri) {
                    $delivery = $this->getResource($deliveryUri);
                    if ($delivery->exists()) {
                        $instance = $this->format($delivery);
                        $values[$instance['id']] = $instance;
                    }
                }
            }
        }

        return $values;
    }

    public function count(array $options = [])
    {
        $id = $this->getOrganisationIdFromOption($options);

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, EligibilityService::CLASS_URI , true);
        $queryBuilder->setCriteria($query);

        $queryBuilder2 = $search->query();
        $query2 = $search->searchType($queryBuilder2, TestCenterService::CLASS_URI, true)
            ->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id);
        $queryBuilder2->setCriteria($query2);

        /** @var QueryJoiner $joiner */
        $joiner = $search->getGateway()->getJoiner();
        $joiner->setQuery($queryBuilder)
            ->join($queryBuilder2)
            ->on(EligibilityService::PROPERTY_TESTCENTER_URI);

        $results = $search->getGateway()->join($joiner);
        $values = [];
        if ($results->total() > 0) {
            /** @var \core_kernel_classes_Resource $resource */
            foreach ($results as $resource) {
                $deliveryUris = $resource->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_DELIVERY_URI));
                foreach ($deliveryUris as $deliveryUri) {
                    $delivery = $this->getResource($deliveryUri);
                    if ($delivery->exists()) {
                        $values[$delivery->getUri()] = $delivery->getUri();
                    }
                }
            }
        }

        return count($values);
    }

    public function insertMultiple(array $entities)
    {
        \common_Logger::i(count($entities));
        parent::insertMultiple($entities);

        foreach ($entities as $entity) {
            $this->getDeliverySynchronizerService()->synchronizeDelivery($entity['id']);
        }

    }

    public function after(array $entities)
    {
//        \common_Logger::i(' *** Delivery to import *** : ' . print_r($this->testToImport, true));
//
//        foreach ($this->testToImport as $deliveryId => $serial) {
//            $delivery = $this->
//            /** @var SynchronisationClient $client */
//            $client = $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
//            $response = $client->getRemoteTestPackageByDelivery();
//            $this->compileTest($delivery, $response['stream'])
//            fclose($zipStream);
//
//            \common_Logger::i(print_r($response, true));
//        }
    }

    protected function getDeliverySynchronizerService()
    {
        return $this->propagate(new DeliverySynchronizerService());
    }

    protected function getOrganisationIdFromOption(array $options = [])
    {
        if (!isset($options['orgId'])) {
            throw new \common_exception_NotFound('Organisation id cannot be retrieved from parameters');
        }
        return $options['orgId'];
    }

}