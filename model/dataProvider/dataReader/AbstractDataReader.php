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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 * @author Yuri Filippovich
 */

namespace oat\taoSync\model\export\dataProvider\dataReader;

use core_kernel_classes_Class;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\oatbox\service\ConfigurableService;

abstract class AbstractDataReader extends ConfigurableService
{
    const TYPE = 'default';

    /**
     * @param string $id
     * @return core_kernel_classes_Resource
     */
    protected function getResource($id)
    {
        return $this->getServiceLocator()->get(Ontology::SERVICE_ID)->getResource($id);
    }

    /**
     * @param string $uri
     * @return core_kernel_classes_Class
     */
    protected function getClass($uri)
    {
        return $this->getServiceLocator()->get(Ontology::SERVICE_ID)->getClass($uri);
    }

    /**
     * @param string $uri
     * @return core_kernel_classes_Property
     */
    protected function getProperty($uri)
    {
        return $this->getServiceLocator()->get(Ontology::SERVICE_ID)->getProperty($uri);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return static::TYPE;
    }

    abstract public function getData(array $params);
}
