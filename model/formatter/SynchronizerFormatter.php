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

/**
 * Interface SynchronizerFormatter
 *
 * To define formatter for resource synchronization
 *
 * @package oat\taoSync\model\formatter
 */
interface SynchronizerFormatter
{
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
    public function format(\core_kernel_classes_Resource $resource, array $options = []);
}