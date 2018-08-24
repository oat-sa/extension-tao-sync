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
 * Copyright (c) 2018 Open Assessment Technologies SA
 */

namespace oat\taoSync\scripts\install;

use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\extension\InstallAction;
use oat\tao\model\event\MetadataModified;
use oat\tao\model\TaoOntology;
use oat\taoSync\model\listener\ListenerService;
use oat\taoSync\model\SyncService;
use oat\taoSync\model\ui\SyncUserFormFactory;
use oat\taoTestCenter\model\gui\form\TreeFormFactory;
use oat\taoTestCenter\model\TestCenterService;

class SetupAssignedTestCenterSyncUser extends InstallAction
{
    use OntologyAwareTrait;

    /**
     * Register the form for SyncUserAssigned in testcenter edit
     *
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        try {
            $this->registerTestCenterSyncUserProperty();
            $this->registerTestCenterSyncUserForm();
            $this->registerTestCenterOrgIdUpdatedEvent();
            return \common_report_Report::createSuccess('SyncUserAssigned property for testcenter has been successfully set.');
        } catch (\Exception $e) {
            return \common_report_Report::createFailure('SyncUserAssigned property for testcenter has failed with message "' . $e->getMessage() . '".');
        }
    }

    /**
     * Register the property for TestCenterSyncUserAssigned
     */
    protected function registerTestCenterSyncUserProperty()
    {
        $property = $this->getProperty(SyncService::PROPERTY_ASSIGNED_SYNC_USER);

        if ($property->exists()) {
            return;
        }

        $property->setRange($this->getClass(TestCenterService::ROLE_TESTCENTER_MANAGER));
        $property->setMultiple(true);
        $property->setDomain($this->getClass(TaoOntology::CLASS_URI_TAO_USER));
        $property->setLabel('Assigned sync user');
        $property->setComment('Assign sync user to the test center');
        $property->setPropertyValue(
            $this->getProperty(OntologyRdfs::RDFS_SUBPROPERTYOF),
            TestCenterService::PROPERTY_MEMBER
        );
    }

    /**
     * Register TestCenter SyncUserAssigned Form
     *
     * @throws \common_Exception
     */
    protected function registerTestCenterSyncUserForm()
    {
        /** @var TreeFormFactory $treeFormFactory */
        $treeFormFactory = $this->getServiceLocator()->get(TreeFormFactory::SERVICE_ID);
        $formFactoryOptions = $treeFormFactory->hasOption(TreeFormFactory::OPTION_FORM_FACTORIES)
            ? $treeFormFactory->getOption(TreeFormFactory::OPTION_FORM_FACTORIES)
            : [];

        $alreadySet = false;
        foreach ($formFactoryOptions as $factory) {
            if ($factory->hasOption('property') && $factory->getOption('property') == SyncService::PROPERTY_ASSIGNED_SYNC_USER) {
                $alreadySet = true;
            }
        }

        if (!$alreadySet) {
            array_splice($formFactoryOptions, 2, 0, [new SyncUserFormFactory([
                'property' => SyncService::PROPERTY_ASSIGNED_SYNC_USER,
                'title' => __('Assign Sync Manager'),
                'isReversed' => true,
            ])]);
            $treeFormFactory->setOption(TreeFormFactory::OPTION_FORM_FACTORIES, $formFactoryOptions);
            $this->registerService(TreeFormFactory::SERVICE_ID, $treeFormFactory);
        }
    }

    /**
     * Register the MetadataModifiedEvent to change assigned users to TestCenter
     */
    protected function registerTestCenterOrgIdUpdatedEvent()
    {
        $this->registerEvent(MetadataModified::class, [ListenerService::SERVICE_ID, 'listen']);
    }
}