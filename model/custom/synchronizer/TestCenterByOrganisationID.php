<?php

namespace oat\taoSync\model\custom\synchronizer;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\taoSync\model\synchronizer\testcenter\RdfTestCenterSynchronizer;
use oat\taoTestCenter\model\TestCenterService;

class TestCenterByOrganisationID extends RdfTestCenterSynchronizer
{
    use OrganisationIdTrait;

    public function fetchOne($id, array $options = [])
    {
        $resource = $this->getResource($id);

        $orgId = $this->getOrganisationIdFromOption($options);
        $orgIdPropertyValue = $resource->getOnePropertyValue($this->getProperty('http://www.taotesting.com/ontologies/synchro.rdf#organisationId'));
        if ($orgIdPropertyValue != $orgId) {
            throw new \common_exception_NotFound('No resource found for id : ' . $id);
        }

        return $this->format($resource);
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
        $query = $search->searchType($queryBuilder, TestCenterService::CLASS_URI , true);
        $query->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id);
        $queryBuilder->setCriteria($query);

        $results = $search->getGateway()->search($queryBuilder);
        $values = [];
        if ($results->total() > 0) {
            /** @var \core_kernel_classes_Resource $resource */
            foreach ($results as $resource) {
                $instance = $this->format($resource);
                $values[$instance['id']] = $instance;
            }
        }

        return $values;
    }

    public function count(array $options = [])
    {
        $id = $this->getOrganisationIdFromOption($options);
        /**
         * @todo apply options
         */

        /** @var ComplexSearchService $search */
        $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);

        $queryBuilder = $search->query();
        $query = $search->searchType($queryBuilder, TestCenterService::CLASS_URI , true);
        $query->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id);
        $queryBuilder->setCriteria($query);

        $results = $search->getGateway()->search($queryBuilder);

        return $results->count();
    }

}