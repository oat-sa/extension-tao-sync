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
use oat\oatbox\event\EventManager;
use oat\oatbox\extension\AbstractAction;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\event\SynchronizationFailed;
use oat\taoSync\model\event\SynchronizationFinished;
use oat\taoSync\model\event\SynchronizationStarted;
use oat\taoSync\model\history\DataSyncHistoryService;
use common_report_Report as Report;

class SynchronizeAll extends AbstractAction
{
    /**
     * @param $params
     * @return Report
     * @throws \common_exception_Error
     */
    public function __invoke($params)
    {
        $actionsToRun = $params['actionsToRun'];
        unset($params['actionsToRun']);

        $params['sync_id'] = $this->getSyncId($params);
        $params['box_id'] = $this->getBoxId();

        $report = Report::createInfo('Synchronizing data');

        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->trigger(
            new SynchronizationStarted($params, $report)
        );

        $success = false;
        try {
            foreach ($actionsToRun as $action){
                if (is_subclass_of($action, Action::class)){
                    $report->add(call_user_func($this->propagate(new $action), $params));
                }
            }
            $success = true;
        } catch (\Exception $e) {
            $report->add(Report::createFailure('An error has occurred : ' . $e->getMessage()));
        } finally {
            if ($success === true) {
                $event = new SynchronizationFinished($params, $report);
            } else {
                $report->add(Report::createFailure('An unexpected PHP error has occurred.'));
                $event = new SynchronizationFailed($params, $report);
            }
            $eventManager->trigger($event);
        }

        return $report;
    }

    /**
     * @param $params
     * @return mixed
     */
    private function getSyncId($params)
    {
        return $this->getServiceLocator()->get(DataSyncHistoryService::SERVICE_ID)->createSynchronisation($params);
    }

    /**
     * @return string
     */
    private function getBoxId()
    {
        return $this->getServiceLocator()->get(PublishingService::SERVICE_ID)->getBoxIdByAction(SynchronizeData::class);
    }
}
