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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\VirtualMachine;

use oat\oatbox\service\ConfigurableService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;

class VmIdentifierService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/VmIdentifierService';

    const PROPERTY_SENDING_BOX_ID = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformSendingBoxId';

    /**
     * Return Virtual Machine identifier: BoxId
     *
     * @return string|null
     */
    public function getBoxId()
    {
        try {
            $environment = $this->findEnvironmentByAction(SynchronizeData::class);
            return $this->getEnvironmentBoxId($environment);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @throws \common_exception_NotFound
     */
    protected function findEnvironmentByAction(string $action) : core_kernel_classes_Resource
    {
        $publishingService =  $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
        $environmentsFound = $publishingService->findByAction($action);
        if (count($environmentsFound) === 0) {
            throw new \common_exception_NotFound('No environment found for action "' . $action . '".');
        }
        return reset($environmentsFound);
    }

    /**
     * Get box ID for environment. If does not exist create a new one.
     *
     * @param $environment
     * @return \core_kernel_classes_Container
     * @throws \common_Exception
     * @throws \core_kernel_classes_EmptyProperty
     */
    private function getEnvironmentBoxId($environment)
    {
        try {
            $boxId = $environment->getUniquePropertyValue($this->getProperty(self::PROPERTY_SENDING_BOX_ID));
        } catch (\core_kernel_classes_EmptyProperty $e) {
            $boxId = $this->setBoxId($environment);
        }
        return $boxId;
    }

    /**
     * Set unique boc identifier for given environment
     *
     * @param \core_kernel_classes_Resource $environment
     * @return \core_kernel_classes_Container
     * @throws \common_Exception
     * @throws \core_kernel_classes_EmptyProperty
     */
    private function setBoxId(\core_kernel_classes_Resource $environment)
    {
        $boxId = uniqid();
        $boxIdProp = $this->getProperty(self::PROPERTY_SENDING_BOX_ID);
        $environment->setPropertyValue($this->getProperty(self::PROPERTY_SENDING_BOX_ID), $boxId);
        return $environment->getUniquePropertyValue($boxIdProp);
    }
}
