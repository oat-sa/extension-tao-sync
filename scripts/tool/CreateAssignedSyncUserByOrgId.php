<?php

namespace oat\taoSync\scripts\tool;

use oat\generis\model\GenerisRdf;
use oat\generis\model\kernel\persistence\smoothsql\search\ComplexSearchService;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\extension\AbstractAction;
use oat\search\base\exception\SearchGateWayExeption;
use oat\tao\model\TaoOntology;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\SyncService;
use oat\taoTestCenter\model\TestCenterService;

class CreateAssignedSyncUserByOrgId extends AbstractAction
{
    use OntologyAwareTrait;

    public function __invoke($params)
    {
        $testCenters = $this->getClass(TestCenterService::CLASS_URI)->getInstances(true);
        $assignedSyncUserProperty = $this->getProperty(SyncService::PROPERTY_ASSIGNED_SYNC_USER);
        $i = 0;
        $success = true;

        /** @var \core_kernel_classes_Resource $testCenter */
        foreach ($testCenters as $testCenter) {
            $id = $testCenter->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));
            if (is_null($id)) {
                continue;
            }

            /** @var ComplexSearchService $search */
            $search = $this->getServiceLocator()->get(ComplexSearchService::SERVICE_ID);
            $queryBuilder = $search->query();
            $query = $search->searchType($queryBuilder, TaoOntology::CLASS_URI_TAO_USER, true);
            $query->add(GenerisRdf::PROPERTY_USER_ROLES)->equals(SyncService::TAO_SYNC_ROLE);
            $query->add(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY)->equals($id);
            $queryBuilder->setCriteria($query);

            try {
                $results = $search->getGateway()->search($queryBuilder);
                if ($results->total() > 0) {
                    foreach ($results as $syncUser) {
                        $success = $success && $syncUser->editPropertyValues($assignedSyncUserProperty, $testCenter);
                        $i++;
                    }
                }
            } catch (SearchGateWayExeption $e) {
            }
        }

        if ($success) {
            return \common_report_Report::createSuccess($i . ' sync users have been successfully assigned to test centers');
        } else {
            return \common_report_Report::createFailure('An error has occurred during sync users migration.');
        }
    }
}
