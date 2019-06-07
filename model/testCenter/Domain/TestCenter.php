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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */
namespace oat\taoSync\model\testCenter\Domain;

class TestCenter
{
    /** @var \core_kernel_classes_Resource $testCenter */
    private $testCenter;

    /** @var string $organisationId */
    private $organisationId;

    /**
     * @param \core_kernel_classes_Resource $testCenter
     * @param string $organisationId
     */
    public function __construct(\core_kernel_classes_Resource $testCenter, $organisationId)
    {
        $this->testCenter = $testCenter;
        $this->organisationId = $organisationId;
    }

    /**
     * @return string
     */
    public function getOrganisationId()
    {
        return $this->organisationId;
    }

    /**
     * @return \core_kernel_classes_Resource
     */
    public function getTestCenter()
    {
        return $this->testCenter;
    }
}
