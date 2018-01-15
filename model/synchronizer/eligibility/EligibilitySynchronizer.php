<?php
/**
 * Created by PhpStorm.
 * User: siwane
 * Date: 12/01/18
 * Time: 17:04
 */

namespace oat\taoSync\model\synchronizer\eligibility;


use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\kernel\persistence\smoothsql\search\QueryJoiner;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoTestCenter\model\EligibilityService;
use oat\taoTestCenter\model\TestCenterService;

class EligibilitySynchronizer extends AbstractResourceSynchronizer
{
    public function getId()
    {
        return 'eligibility';
    }

    protected function getRootClass()
    {
        return $this->getClass(EligibilityService::CLASS_URI);
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
        $query = $search
            ->searchType($queryBuilder, $this->getRootClass()->getUri() , true)
        ;
        $queryBuilder->setCriteria($query);

        $queryBuilder2 = $search->query();
        $query2 = $search->searchType($queryBuilder2, TestCenterService::CLASS_URI, true)
            ->add('http://www.taotesting.com/ontologies/synchro.rdf#organisationId')->equals($id)
        ;
        $queryBuilder2->setCriteria($query2);

        /** @var QueryJoiner $joiner */
        $joiner = $search->getGateway()->getJoiner();
        $joiner->setQuery($queryBuilder)
            ->join($queryBuilder2)
            ->on('http://www.tao.lu/Ontologies/TAOProctor.rdf#EligibileTestCenter');

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
        $results = $search->getGateway()->join($joiner);

        $values = [];
        if ($results->total() > 0) {
            foreach ($results as $resource) {
                $instance = $this->format($resource);
                $values[$instance['id']] = $instance;
            }
        }

//        \common_Logger::i(print_r($values, true));
        return $values;
    }

}