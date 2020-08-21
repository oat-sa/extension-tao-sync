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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

namespace oat\taoSync\scripts\install;
use common_Exception;
use common_report_Report;
use oat\generis\model\data\event\ResourceDeleted;
use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\EntityChecksumCacheService;

/**
 * sudo -u www-data php index.php 'oat\taoSync\scripts\install\RegisterEntityChecksumCache'
 */
class RegisterEntityChecksumCache extends InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws common_Exception
     */
    public function __invoke($params)
    {
        $service = new EntityChecksumCacheService([
            EntityChecksumCacheService::OPTION_PERSISTENCE => 'default_kv',
        ]);

        $this->registerService(EntityChecksumCacheService::SERVICE_ID, $service);

        $this->registerEvent(ResourceDeleted::class, [EntityChecksumCacheService::SERVICE_ID, 'entityDeleted']);

        return new common_report_Report(common_report_Report::TYPE_SUCCESS, 'EntityChecksumCacheService was registered.');
    }
}
