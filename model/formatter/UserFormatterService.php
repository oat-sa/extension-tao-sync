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

namespace oat\taoSync\model\formatter;

use oat\generis\model\OntologyAwareTrait;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\scripts\tool\RedisTable;
use oat\taoTestCenter\model\TestCenterAssignment;

class UserFormatterService extends FormatterService
{
    use OrganisationIdTrait;
    use OntologyAwareTrait;

    /**
     * Format a resource to an array
     *
     * Add a checksum to identify the resource content
     * Add resource triples as properties if $withProperties param is true
     *
     * @param \core_kernel_classes_Resource $resource
     * @param array $options
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function format(\core_kernel_classes_Resource $resource, array $options = [], array $params = [])
    {
        if (array_key_exists(self::OPTION_INCLUDED_PROPERTIES, $options) && $options[self::OPTION_INCLUDED_PROPERTIES] === true) {
            $withProperties = true;
        } else {
            $withProperties = false;
        }

        $redisTable = new RedisTable();
        $this->propagate($redisTable);

        $value = $redisTable->get($resource->getUri());

        if (is_null($value) || $value == false) {
            $triplesArray = $resource->getRdfTriples()->toArray();
            if ($redisTable->set($resource->getUri(), serialize($triplesArray))) {
                $redisTable->cleanTTInfo($resource);
            }
        } else {
            $triplesArray = unserialize($value);
        }

        $properties = $this->filterProperties(
            $triplesArray, $options, $params
        );

        return [
            'id' => $resource->getUri(),
            'checksum' => $this->hashProperties($properties),
            'properties' => ($withProperties === true) ? $properties : [],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function filterProperties(array $triples, array $options = [], array $params = [])
    {
        $properties = parent::filterProperties($triples, $options);

        //fix to only apply the eligibility available in this organisation.
        if (isset($properties[TestCenterAssignment::PROPERTY_TESTTAKER_ASSIGNED])) {
            $orgId          = $this->getOrganisationIdFromOption($params);
            $eligibilities  = $this->getEligibilitiesByOrganisationId($orgId);

            if (!is_array($properties[TestCenterAssignment::PROPERTY_TESTTAKER_ASSIGNED])) {
                $properties[TestCenterAssignment::PROPERTY_TESTTAKER_ASSIGNED] =
                [$properties[TestCenterAssignment::PROPERTY_TESTTAKER_ASSIGNED]];
            }

            $userAssignments = [];
            foreach ($eligibilities as $eligibility) {
                if (in_array($eligibility->getUri(), $properties[TestCenterAssignment::PROPERTY_TESTTAKER_ASSIGNED])) {
                    $userAssignments[] = $eligibility->getUri();
                }
            }
            $properties[TestCenterAssignment::PROPERTY_TESTTAKER_ASSIGNED] = $userAssignments;
        }

        return $properties;
    }
}