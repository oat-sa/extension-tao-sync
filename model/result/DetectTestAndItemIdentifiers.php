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
namespace oat\taoSync\model\result;

use oat\generis\model\OntologyAwareTrait;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoQtiItem\model\qti\ImportService;
use taoQtiTest_models_classes_QtiTestService as QtiTestService;

class DetectTestAndItemIdentifiers
{
    use OntologyAwareTrait;

    /**
     * @param $deliveryId
     * @param $variable
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    public function detect($deliveryId, $variable)
    {
        $remoteNamespace = explode('#', $deliveryId);
        $variable = (array) $variable;
        $testIdentifier = null;
        if (isset($variable['test'])) {
            $delivery = $this->getResource($deliveryId);
            $test = $this->getResource($delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN)));
            $qtiTestIdentifier = (string) $test->getOnePropertyValue($this->getProperty(QtiTestService::PROPERTY_QTI_TEST_IDENTIFIER));
            $testIdentifier = $qtiTestIdentifier ? implode('#', [$remoteNamespace[0], $qtiTestIdentifier]) : null;
        }

        $itemIdentifier = null;
        if (isset($variable['item'])) {
            $item = $this->getResource($variable['item']);
            $qtiItemIdentifier = (string) $item->getOnePropertyValue($this->getProperty(ImportService::PROPERTY_QTI_ITEM_IDENTIFIER));
            $itemIdentifier = $qtiItemIdentifier ? implode('#', [$remoteNamespace[0], $qtiItemIdentifier]) : null;
        }

        return [$testIdentifier, $itemIdentifier];
    }
}