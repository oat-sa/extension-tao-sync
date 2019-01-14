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

namespace oat\taoSync\model\SynchronizationHistory;

use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\SyncLog\SyncLogFilter;
use oat\taoSync\model\SyncLog\Storage\SyncLogStorageInterface;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;

/**
 * Class ClientSynchronizationHistoryService
 * @package oat\taoSync\model\SynchronizationHistory
 */
class ClientSynchronizationHistoryService extends SynchronizationHistoryService
{
    /**
     * @param SyncLogFilter $filter
     */
    protected function setFilters(SyncLogFilter $filter)
    {
        parent::setFilters($filter);
        $this->setBoxIdFilter($filter);
        $this->setOrganizationIdFilter($filter);
    }

    /**
     * Filter payload data by box id if available.
     *
     * @param SyncLogFilter $filter
     */
    private function setBoxIdFilter(SyncLogFilter $filter)
    {
        $boxId = $this->getServiceLocator()->get(PublishingService::SERVICE_ID)->getBoxIdByAction(SynchronizeData::class);

        if (!empty($boxId)) {
            $filter->eq(SyncLogStorageInterface::COLUMN_BOX_ID, $boxId);
        }
    }

    /**
     * Filter payload data by organization id if available.
     *
     * @param SyncLogFilter $filter
     */
    private function setOrganizationIdFilter(SyncLogFilter $filter)
    {
        $orgId = $this->currentUser->getPropertyValues(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);

        if (isset($orgId[0])) {
            $filter->eq(SyncLogStorageInterface::COLUMN_ORGANIZATION_ID, $orgId[0]);
        }
    }
}
