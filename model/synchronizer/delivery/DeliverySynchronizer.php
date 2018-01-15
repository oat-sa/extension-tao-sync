<?php

namespace oat\taoSync\model\synchronizer\delivery;

use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

class DeliverySynchronizer extends AbstractResourceSynchronizer
{
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
        /**
         * @todo apply options
         */

        $id = '1234';

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
            ->on('http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileTestCenter')
        ;

        $results = $search->getGateway()->join($joiner);

//        $values = [];
        if ($results->total() > 0) {
            /** @var \core_kernel_classes_Resource $resource */
            foreach ($results as $resource) {
                $deliveryUri = $resource->getOnePropertyValue($this->getProperty('http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileDelivery'));
                $delivery = $this->getResource($deliveryUri);
                $instance = $this->format($delivery);
                \common_Logger::i(print_r($instance,true));
                $values[$instance['id']] = $instance;
            }
        }

//        $queryBuilder3 = $search->query();
//        $query3 = $search->searchType($queryBuilder3, $this->getRootClass()->getUri(), true);
////        ->join($queryBuilder3)
////        ->on('http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileDelivery')
//        $queryBuilder3->setCriteria($query3);
//
//        $results = $search->getGateway()->join($joiner);
//
//        $values = [];
//        if ($results->total() > 0) {
//            foreach ($results as $resource) {
//                $instance = $this->format($resource);
//                $values[$instance['id']] = $instance;
//            }
//        }
//        $joiner->sort($sorting);
//
//        if (!is_null($offset)) {
//            $joiner->setOffset($offset);
//        }
//
//        if (!is_null($limit)) {
//            $joiner->setLimit($limit);
//        }
//


//        \common_Logger::i(print_r($values, true));
        return $values;
    }

}