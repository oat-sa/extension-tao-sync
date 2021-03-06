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

namespace oat\taoSync\model\synchronizer\user\administrator;

use oat\taoSync\model\synchronizer\user\UserSynchronizer;
use oat\taoTestCenter\model\TestCenterService;

class RdfAdministratorSynchronizer extends UserSynchronizer implements AdministratorSynchronizer
{
    /**
     * Get the synchronizer identifier
     *
     * @return string
     */
    public function getId()
    {
        return self::SYNC_ADMINISTRATOR;
    }

    /**
     * Get the role defining what an administrator is
     *
     * @return string
     */
    public function getUserRole()
    {
        return TestCenterService::ROLE_TESTCENTER_ADMINISTRATOR;
    }
}
