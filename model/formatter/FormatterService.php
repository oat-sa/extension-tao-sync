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

use oat\oatbox\service\ConfigurableService;

class FormatterService extends ConfigurableService implements SynchronizerFormatter
{
    const OPTION_ONLY_FIELDS = 'only-fields';
    const OPTION_EXCLUDED_FIELDS = 'excluded-fields';
    const OPTION_INCLUDED_PROPERTIES = 'with-properties';

    /**
     * Format a resource to an array
     *
     * Add a checksum to identify the resource content
     * Add resource triples as properties if $withProperties param is true
     *
     * @param \core_kernel_classes_Resource $resource
     * @param array $options
     * @return array
     */
    public function format(\core_kernel_classes_Resource $resource, array $options = [])
    {
        if (array_key_exists(self::OPTION_INCLUDED_PROPERTIES, $options) && $options[self::OPTION_INCLUDED_PROPERTIES] === true) {
            $withProperties = true;
        } else {
            $withProperties = false;
        }

        $properties = $this->filterProperties($resource->getRdfTriples()->toArray());
        return [
            'id' => $resource->getUri(),
            'checksum' => $this->hashProperties($properties),
            'properties' => ($withProperties === true) ? $properties : [],
        ];
    }

    /**
     * Filter resource triples against the given $options
     *
     * $options is optional and allow to filter by:
     *  - only fields at only-fields $options key
     *  - all fields excepted fields at excluded-fields $options key
     *
     * return an array of $properties = [$predicate => $object, ...]
     *
     * @param \core_kernel_classes_Triple[] $triples
     * @param array $options
     * @return array
     */
    protected function filterProperties(array $triples, array $options = [])
    {
        if (array_key_exists(self::OPTION_ONLY_FIELDS, $options) && is_array($options[self::OPTION_ONLY_FIELDS])) {
            $fields = $options[self::OPTION_ONLY_FIELDS];
        } else {
            $fields = [];
        }

        if (array_key_exists(self::OPTION_EXCLUDED_FIELDS, $options) && is_array($options[self::OPTION_EXCLUDED_FIELDS])) {
            $excludedFields = $options[self::OPTION_EXCLUDED_FIELDS];
        } else {
            $excludedFields = [];
        }

        $properties = [];

        /** @var \core_kernel_classes_Triple $triple */
        foreach ($triples as $triple) {
            $predicate = $object = null;
            if (!empty($fields)) {
                if (in_array($triple->predicate, $fields)) {
                    $predicate = $triple->predicate;
                    $object = $triple->object;
                }
            } else {
                if (!in_array($triple->predicate, $excludedFields)) {
                    $predicate = $triple->predicate;
                    $object = $triple->object;
                }
            }

            if (!is_null($predicate) && !is_null($object)) {
                if (array_key_exists($predicate, $properties)) {
                    if ($properties[$predicate] == $object) {
                        continue;
                    }
                    $value = is_array($properties[$predicate]) ? $properties[$predicate] : [$properties[$predicate]];
                    $value[] = $object;
                } else {
                    $value = $object;
                }
                $properties[$predicate] = $value;
            }
        }

        return $properties;
    }

    /**
     * Hash properties to have a checksum of $properties
     *
     * @param array $properties
     * @return string
     */
    protected function hashProperties(array $properties)
    {
        return md5(serialize($properties));
    }
}