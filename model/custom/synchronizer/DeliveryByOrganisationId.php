<?php

namespace oat\taoSync\model\custom\synchronizer;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\taoSync\model\synchronizer\delivery\RdfDeliverySynchronizer;
use oat\taoSync\model\synchronizer\eligibility\RdfEligibilitySynchronizer;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

class DeliveryByOrganisationId extends RdfDeliverySynchronizer
{
    use OrganisationIdTrait;

    public function fetch(array $options = [])
    {
        $id = $this->getOrganisationIdFromOption($options);

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

}