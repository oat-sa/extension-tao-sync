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

namespace oat\taoSync\scripts\tool;

use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\generis\model\OntologyRdfs;
use oat\generis\model\WidgetRdf;
use oat\oatbox\extension\InstallAction;
use oat\tao\helpers\form\ValidationRuleRegistry;
use oat\tao\model\TaoOntology;
use oat\tao\model\WidgetDefinitions;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoResultServer\models\classes\implementation\OntologyService;
use oat\taoDelivery\model\fields\DeliveryFieldsService;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoProctoring\model\ProctorService;
use oat\taoSync\model\Entity;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\delivery\DeliveryByOrganisationId;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\user\AdministratorByOrganisationId;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\eligibility\EligibilityByOrganisationId;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\user\ProctorByOrganisationId;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\user\TestTakerByOrganisationId;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoSync\model\synchronizer\testcenter\TestCenterSynchronizer;
use oat\taoSync\model\SyncService;
use oat\taoTestCenter\model\TestCenterService;

/**
 * Class RegisterSyncService
 *
 * sudo -u www-data php index.php '\oat\taoSync\scripts\install\RegisterSyncServiceByOrgId'
 *
 * @package oat\taoSync\scripts\install
 */
class RegisterSyncServiceByOrgId extends InstallAction
{
    use OntologyAwareTrait;

    /**
     * Setup the synchronisation to scope it to a test center organisation id property
     *
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        $this->createOrganisationIdProperty();
        $this->migrateSyncServiceSynchronizers();
        $this->addOrganisationIdPropertyToCsvImporter();

        return \common_report_Report::createSuccess('SyncService successfully registered.');
    }

    /**
     * Create test center organisation id property
     */
    protected function createOrganisationIdProperty()
    {
        $property = $this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);

        if ($property->exists()) {
            return;
        }

        $property->setType($this->getClass(OntologyRdf::RDF_PROPERTY));
        $property->setLabel('Organisation Id');
        $property->setComment('Allow to synchronize a client against a remote testcenter identified by organisation id');
        $property->setDomain($this->getClass(TestCenterService::CLASS_URI));
        $property->setRange($this->getClass(OntologyRdfs::RDFS_LITERAL));
        $property->setLgDependent(false);
        $property->setPropertiesValues(array(
            ValidationRuleRegistry::PROPERTY_VALIDATION_RULE => 'notEmpty',
            WidgetRdf::PROPERTY_WIDGET => WidgetDefinitions::PROPERTY_TEXTBOX,
            TaoOntology::PROPERTY_GUI_ORDER => 1,
        ));
    }

    /**
     * Migrate the SyncService config synchronizers
     *
     * If old synchronizer exists migrate it
     * In all cases register syncrhonizers by orig id
     *
     * @throws \common_Exception
     */
    protected function migrateSyncServiceSynchronizers()
    {
        /** @var SyncService $service */
        $service = $this->getServiceLocator()->get(SyncService::SERVICE_ID);
        $synchronizers = [];

        if (
            $service->hasOption(SyncService::OPTION_SYNCHRONIZERS)
            && is_array($service->getOption(SyncService::OPTION_SYNCHRONIZERS))
        ) {
            $synchronizers = $service->getOption(SyncService::OPTION_SYNCHRONIZERS);

            if (array_key_exists(TestCenterSynchronizer::SYNC_TEST_CENTER, $synchronizers)) {
                $oldSynchronizer = $synchronizers[TestCenterSynchronizer::SYNC_TEST_CENTER];
                $testcenterSynchronizer = new TestCenterByOrganisationId($oldSynchronizer->getOptions());
            }

            if (array_key_exists(AdministratorByOrganisationId::SYNC_ADMINISTRATOR, $synchronizers)) {
                $oldSynchronizer = $synchronizers[AdministratorByOrganisationId::SYNC_ADMINISTRATOR];
                $administractorSynchronizer = new AdministratorByOrganisationId($oldSynchronizer->getOptions());
            }

            if (array_key_exists(ProctorByOrganisationId::SYNC_PROCTOR, $synchronizers)) {
                $oldSynchronizer = $synchronizers[ProctorByOrganisationId::SYNC_PROCTOR];
                $proctorSynchronizer = new ProctorByOrganisationId($oldSynchronizer->getOptions());
            }

            if (array_key_exists(TestTakerByOrganisationId::SYNC_TEST_TAKER, $synchronizers)) {
                $oldSynchronizer = $synchronizers[TestTakerByOrganisationId::SYNC_TEST_TAKER];
                $testtakerSynchronizer = new TestTakerByOrganisationId($oldSynchronizer->getOptions());
            }

            if (array_key_exists(EligibilityByOrganisationId::SYNC_ELIGIBILITY, $synchronizers)) {
                $oldSynchronizer = $synchronizers[EligibilityByOrganisationId::SYNC_ELIGIBILITY];
                $eligibilitySynchronizer = new EligibilityByOrganisationId($oldSynchronizer->getOptions());
            }

            if (array_key_exists(DeliveryByOrganisationId::SYNC_DELIVERY, $synchronizers)) {
                $oldSynchronizer = $synchronizers[DeliveryByOrganisationId::SYNC_DELIVERY];
                $deliverySynchronizer = new DeliveryByOrganisationId($oldSynchronizer->getOptions());

            }
        }

        if (!isset($testcenterSynchronizer)) {
            $testcenterSynchronizer = new TestCenterByOrganisationId(array(
                AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                    TaoOntology::PROPERTY_UPDATED_AT,
                    Entity::CREATED_AT,
                )
            ));
        }

        if (!isset($administractorSynchronizer)) {
            $administractorSynchronizer = new AdministratorByOrganisationId(array(
                AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                    TaoOntology::PROPERTY_UPDATED_AT,
                    Entity::CREATED_AT,
                )
            ));
        }

        if (!isset($proctorSynchronizer)) {
            $proctorSynchronizer = new ProctorByOrganisationId(array(
                AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                    TaoOntology::PROPERTY_UPDATED_AT,
                    Entity::CREATED_AT,
                )
            ));
        }

        if (!isset($testtakerSynchronizer)) {
            $testtakerSynchronizer = new TestTakerByOrganisationId(array(
                AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                    TaoOntology::PROPERTY_UPDATED_AT,
                    Entity::CREATED_AT,
                )
            ));
        }

        if (!isset($eligibilitySynchronizer)) {
            $eligibilitySynchronizer = new EligibilityByOrganisationId(array(
                AbstractResourceSynchronizer::OPTIONS_EXCLUDED_FIELDS => array(
                    TaoOntology::PROPERTY_UPDATED_AT,
                    Entity::CREATED_AT,
                )
            ));
        }

        if (!isset($deliverySynchronizer)) {
            $deliverySynchronizer = new DeliveryByOrganisationId(array(
                AbstractResourceSynchronizer::OPTIONS_FIELDS => array(
                    OntologyRdf::RDF_TYPE,
                    OntologyRdfs::RDFS_LABEL,
                    OntologyService::PROPERTY_RESULT_SERVER,
                    DeliveryContainerService::PROPERTY_MAX_EXEC,
                    DeliveryAssemblyService::PROPERTY_DELIVERY_DISPLAY_ORDER_PROP,
                    DeliveryContainerService::PROPERTY_ACCESS_SETTINGS,
                    DeliveryAssemblyService::PROPERTY_END,
                    DeliveryFieldsService::PROPERTY_CUSTOM_LABEL,
                    ProctorService::ACCESSIBLE_PROCTOR,
                    DeliveryAssemblyService::PROPERTY_START,
                )
            ));
        }

        $synchronizers[TestCenterSynchronizer::SYNC_TEST_CENTER] = $testcenterSynchronizer;
        $synchronizers[AdministratorByOrganisationId::SYNC_ADMINISTRATOR] = $administractorSynchronizer;
        $synchronizers[ProctorByOrganisationId::SYNC_PROCTOR] = $proctorSynchronizer;
        $synchronizers[TestTakerByOrganisationId::SYNC_TEST_TAKER] = $testtakerSynchronizer;
        $synchronizers[EligibilityByOrganisationId::SYNC_ELIGIBILITY] = $eligibilitySynchronizer;
        $synchronizers[DeliveryByOrganisationId::SYNC_DELIVERY] = $deliverySynchronizer;

        $service->setOption(SyncService::OPTION_SYNCHRONIZERS, $synchronizers);
        $this->registerService(SyncService::SERVICE_ID, $service);
    }

    protected function addOrganisationIdPropertyToCsvImporter()
    {
        return call_user_func($this->propagate(new SetupSyncUserCsvImporterByOrgId()), []);
    }
}