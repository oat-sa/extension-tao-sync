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

namespace oat\taoSync\model\synchronizer\delivery;

use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;

class RdfDeliverySynchronizer extends AbstractResourceSynchronizer implements DeliverySynchronizer
{
    /**
     * Get the synchronizer identifier
     *
     * @return string
     */
    public function getId()
    {
        return self::SYNC_DELIVERY;
    }

    /**
     * RdfDeliverySynchronizer constructor.
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $fields = $this->getOption(self::OPTIONS_FIELDS);
        $excludedFields = $this->getOption(self::OPTIONS_EXCLUDED_FIELDS);
        if (!empty($fields) && !in_array(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI, $fields)) {
            $fields[] = DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI;
            $this->setOption(self::OPTIONS_FIELDS, $fields);
        } elseif (!empty($excludedFields) && in_array(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI, $excludedFields)) {
            unset($excludedFields[array_search(DeliverySynchronizerService::DELIVERY_TEST_PACKAGE_URI, $excludedFields)]);
            $this->setOption(self::OPTIONS_EXCLUDED_FIELDS, $excludedFields);
        }
    }

    /**
     * Insert multiple deliveries and import remote associated test as compiled delivery
     *
     * @param array $entities
     * @return array
     */
    public function insertMultiple(array $entities)
    {
        parent::insertMultiple($entities);
        $errors = $created = [];
        foreach ($entities as $entity) {
            try {
                $this->getDeliverySyncService()->synchronizeDelivery($entity['id']);
                $created[] = $entity['id'];
            } catch (\common_Exception $e) {
                $errors[] = $entity['id'];
            }
        }
        if (!empty($errors)) {
            $this->deleteMultiple($errors);
        }

        return $created;
    }

    /**
     * Update multiple deliveries
     *
     * Because of test is compiled locally, properties about compilation is excluded from sync
     *
     * @param array $entities
     */
    public function updateMultiple(array $entities)
    {
        $fields = $this->getOption(self::OPTIONS_FIELDS) ?: [];
        $excludedFields = $this->getOption(self::OPTIONS_EXCLUDED_FIELDS) ?: [];

        foreach ($entities as $entity) {
            $resource = $this->getResource($entity['id']);
            $properties = isset($entity['properties']) ? $entity['properties'] : [];
            foreach ($properties as $property => $value) {
                if (!empty($fields)) {
                    if (in_array($property, $fields)) {
                        $resource->removePropertyValues($this->getProperty($property));
                    }
                } else {
                    if (!in_array($property, $excludedFields)) {
                        $resource->removePropertyValues($this->getProperty($property));
                    }
                }
            }

            $resource->setPropertiesValues($properties);
        }
    }

    /**
     * Get the root class of entity to synchronize
     *
     * @return \core_kernel_classes_Class
     */
    protected function getRootClass()
    {
        return $this->getClass(DeliveryAssemblyService::CLASS_URI);
    }

    /**
     * Get the service to synchronize delivery tests
     *
     * @return DeliverySynchronizerService
     */
    protected function getDeliverySyncService()
    {
        return $this->getServiceLocator()->get(DeliverySynchronizerService::SERVICE_ID);
    }

}