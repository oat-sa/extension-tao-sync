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

namespace oat\taoSync\scripts\tool\synchronisation;

use oat\oatbox\extension\AbstractAction;
use oat\taoSync\model\ResultService;

/**
 * Class SynchronizeResult
 *
 * Action to launch results synchronisation
 *
 * @package oat\taoSync\scripts\tool
 */
class SynchronizeResult extends AbstractAction
{
    /**
     * Launch the synchronisation result
     *
     * @param $params
     * @return \common_report_Report
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \common_exception_NoImplementation
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function __invoke($params)
    {
        return $this->getSyncResultService()->synchronizeResults($params);
    }

    /**
     * @return ResultService
     */
    protected function getSyncResultService()
    {
        return $this->getServiceLocator()->get(ResultService::SERVICE_ID);
    }
}
