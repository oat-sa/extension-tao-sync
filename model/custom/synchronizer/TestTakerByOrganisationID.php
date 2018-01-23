<?php

namespace oat\taoSync\model\custom\synchronizer;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\taoSync\model\synchronizer\user\testtaker\RdfTestTakerSynchronizer;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

class TestTakerByOrganisationID extends RdfTestTakerSynchronizer
{
    use OrganisationIdTrait;

    public function fetch(array $options = [])
    {
        $id = $this->getOrganisationIdFromOption($options);
        /**
         * @todo apply options
         */

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
                $testtakers = $resource->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_TESTTAKER_URI));
                foreach ($testtakers as $testtakerUri) {
                    $testtaker = $this->getResource($testtakerUri);
                    if ($testtaker->exists()) {
                        $instance = $this->format($testtaker);
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
        $testtakers = [];
        if ($results->total() > 0) {
            foreach ($results as $resource) {
                $testtakerUris = $resource->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_TESTTAKER_URI));
                foreach ($testtakerUris as $testtakerUri) {
                    $testtaker = $this->getResource($testtakerUri);
                    if ($testtaker->exists()) {
                        $testtakers[$testtaker->getUri()] = $testtaker->getUri();
                    }
                }
            }
        }

        return count($testtakers);
    }

}