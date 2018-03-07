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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoSync\scripts\tool\oauth;

use oat\taoOauth\scripts\tools\GenerateCredentials;
use oat\taoSync\model\SyncService;

class GenerateOauthCredentials extends GenerateCredentials
{
    /**
     * Generate an oauth consumer and add to it taoSync role
     *
     * @return \common_report_Report
     * @throws \core_kernel_users_Exception
     */
    protected function run()
    {
        $report = parent::__invoke([]);
        $this->addUserRoles($this->createdConsumer);
        return $report;
    }

    /**
     * Attach taoSync role to consumer
     *
     * @param \core_kernel_classes_Resource $consumer
     * @throws \core_kernel_users_Exception
     */
    protected function addUserRoles(\core_kernel_classes_Resource $consumer)
    {
        \core_kernel_users_Service::singleton()->attachRole($consumer, $this->getResource(SyncService::TAO_SYNC_ROLE));
    }
}