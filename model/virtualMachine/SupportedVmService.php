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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA
 */

namespace oat\taoSync\model\virtualMachine;

use oat\oatbox\service\ConfigurableService;
use oat\tao\model\ClassServiceTrait;
use oat\tao\model\GenerisServiceTrait;

/**
 * Service methods to manage the VMs
 *
 * @access public
 */
class SupportedVmService extends ConfigurableService
{
    use ClassServiceTrait;
    use GenerisServiceTrait;

    const SERVICE_ID = 'taoSync/SupportedVmService';

    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoVM';

    const PROPERTY_VM_VERSION = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoVMVersion';

    /**
     * return the group top level class
     *
     * @access public
     * @return \core_kernel_classes_Class
     */
    public function getRootClass()
    {
        return $this->getClass(self::CLASS_URI);
    }

    /**
     * @return array
     */
    public function getSupportedVmVersions()
    {
        $class = $this->getRootClass();
        $property = $this->getProperty(self::PROPERTY_VM_VERSION);
        $supportedVersions = $class->getInstancesPropertyValues($property);

        return array_column($supportedVersions, 'literal');
    }
}
