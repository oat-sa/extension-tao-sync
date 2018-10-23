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

namespace oat\taoSync\scripts\tool;

use common_persistence_KeyValuePersistence;
use oat\oatbox\service\ServiceManagerAwareInterface;
use oat\oatbox\service\ServiceManagerAwareTrait;

class RedisTable implements ServiceManagerAwareInterface
{
    use ServiceManagerAwareTrait;
    const PREFIX = 'mapper_tt_info_';

    private $persistence;
    /**
     * @param $key
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function set($key, $value)
    {
        return $this->getPersistence()->set($this->computeKey($key), $value);
    }
    /**
     * @param $key
     * @return string
     * @throws \Exception
     */
    public function get($key)
    {
        return $this->getPersistence()->get($this->computeKey($key));
    }

    /**
     * @param \core_kernel_classes_Resource $resource
     */
    public function cleanTTInfo(\core_kernel_classes_Resource $resource)
    {
        $propertiesRaw = [
            'roles',
            'http://www.tao.lu/Ontologies/TAODelivery.rdf#applicationKey',
            'http://www.tao.lu/Ontologies/generis.rdf#encryptionKey',
            'http://www.tao.lu/Ontologies/generis.rdf#userFirstName',
            'http://www.tao.lu/Ontologies/generis.rdf#userRoles',
            'http://www.tao.lu/Ontologies/generis.rdf#userUILg',
            'http://www.tao.lu/Ontologies/generis.rdf#userDefLg'

        ];
        foreach ($propertiesRaw as $value) {
            $property = new \core_kernel_classes_Property($value);
            $values    = $resource->getPropertyValues($property);
            foreach ($values as $v) {
                $resource->removePropertyValue($property, $v);
            }
        }
    }

    private function computeKey($key)
    {
        return static::PREFIX . $key;
    }
    /**
     * @throws \Exception
     */
    private function getPersistence()
    {
        if (is_null($this->persistence)) {
            $persistenceId = 'redis';

            $persistence = $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);
            if (!$persistence instanceof common_persistence_KeyValuePersistence) {
                throw new \Exception('Only common_persistence_KeyValuePersistence supported');
            }
            $this->persistence = $persistence;
        }
        return $this->persistence;
    }
}