<?php

namespace oat\taoSync\model\synchronizer\testcenter;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoTestCenter\model\TestCenterService;

class TestCenterByOrganisationID extends AbstractResourceSynchronizer implements TestCenterSynchronizer
{
    use OntologyAwareTrait;

    public function getId()
    {
        return self::SYNC_ID;
    }

    public function getRootClass()
    {
        return $this->getClass(TestCenterService::CLASS_URI);
    }

    public function fetch(array $options = [])
    {
        /**
         * @todo apply options
         */
        $id = $this->getOrganisationId();

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();

        $query = $search
            ->searchType($queryBuilder, TestCenterService::CLASS_URI , true)
            ->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id);

        $queryBuilder->setCriteria($query);
        $results = $search->getGateway()->search($queryBuilder);

        $values = [];
        if ($results->total() > 0) {
            foreach ($results as $resource) {
                $instance = $this->format($resource);
                $values[$instance['id']] = $instance;
            }
        }

        return $values;
    }

    public function fetchOne($id)
    {
        $resource = $this->getResource($id);
        $id = $resource->getOnePropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#organisationId'));
        if ($id != $this->getOrganisationId()) {
            throw new \common_exception_NotFound('No resource found for id : ' . $id);
        }
        return $this->format($resource);
    }

    public function count()
    {
        $id = $this->getOrganisationId();

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
        $queryBuilder = $search->query();

        $query = $search
            ->searchType($queryBuilder, TestCenterService::CLASS_URI , true)
            ->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id);

        $queryBuilder->setCriteria($query);
        $results = $search->getGateway()->search($queryBuilder);

        return $results->count();
    }

    protected function getOrganisationId()
    {
        return '1234';
    }
}