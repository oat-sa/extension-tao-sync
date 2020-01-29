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

namespace oat\taoSync\controller;

use oat\taoSync\model\VirtualMachine\SupportedVmService;

class SupportedVm extends \tao_actions_SaSModule
{
    protected $service;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     *
     * @see tao_actions_RdfController::getClassService()
     */
    public function getClassService()
    {
        if ($this->service == null) {
            $this->service = $this->getServiceLocator()->get(SupportedVmService::SERVICE_ID);
        }

        return $this->service;
    }
}
