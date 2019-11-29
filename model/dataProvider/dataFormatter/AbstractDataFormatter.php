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
 * @author Oleksandr Zagovorychev <zagovorichev@1pt.com>
 */

namespace oat\taoSync\export\dataProvider\dataFormatter;

use core_kernel_classes_Resource;
use oat\oatbox\service\ConfigurableService;

abstract class AbstractDataFormatter extends ConfigurableService
{

    /**
     * @param core_kernel_classes_Resource $resource
     * @return array
     */
    abstract public function format(core_kernel_classes_Resource $resource);

    /**
     * @param core_kernel_classes_Resource[] $resources
     * @return array
     */
    public function formatAll(array $resources)
    {
        $data = [];

        foreach ($resources as $resource) {
            $data[] = $this->format($resource);
        }

        return $data;
    }
}
