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

use oat\oatbox\action\Action;
use oat\oatbox\extension\AbstractAction;

class SynchronizeAll extends AbstractAction
{
    /**
     * @param $params
     * @return \common_report_Report
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {
        $actionsToRun = $params['actionsToRun'];
        unset($params['actionsToRun']);

        $report = \common_report_Report::createInfo('Synchronizing data');
        try {
            foreach ($actionsToRun as $action){
                if (is_subclass_of($action, Action::class)){
                    $report->add(call_user_func($this->propagate(new $action), $params));
                }
            }
        } catch (\Exception $e) {
            $report->add(\common_report_Report::createFailure('An error has occurred : ' . $e->getMessage()));
        }
        return $report;
    }

}