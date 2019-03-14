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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\SyncLog;

use common_report_Report as Report;
use oat\oatbox\service\ConfigurableService;

/**
 * Class SyncLogClientStateUpdater
 * @package oat\taoSync\model\SyncLog
 */
class SyncLogClientStateUpdater extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncLogClientStateUpdater';

    /**
     * @param array $syncParams
     * @param Report $clientStateReport
     * @throws \common_exception_Error
     * @throws \oat\taoSync\model\Exception\SyncLogEntityNotFound
     */
    public function update($syncParams, $clientStateReport)
    {
        $syncLogService = $this->getSyncLogService();
        $syncLogEntity = $syncLogService->getBySyncIdAndBoxId(
            $syncParams[SyncLogServiceInterface::PARAM_SYNC_ID],
            $syncParams[SyncLogServiceInterface::PARAM_BOX_ID]
        );

        $parser = $this->getSyncLogClientStateParser();
        $syncLogEntity->setClientState($parser->parseSyncClientState($clientStateReport));
        $syncLogService->update($syncLogEntity);
    }

    /**
     * @return SyncLogClientStateParser
     */
    private function getSyncLogClientStateParser()
    {
        return $this->getServiceLocator()->get(SyncLogClientStateParser::SERVICE_ID);
    }

    /**
     * @return SyncLogServiceInterface
     */
    private function getSyncLogService()
    {
        return $this->getServiceLocator()->get(SyncLogServiceInterface::SERVICE_ID);
    }
}
