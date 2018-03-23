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

namespace oat\taoSync\model\synchronizer\testcenter;

use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoTestCenter\model\TestCenterService;

class RdfTestCenterSynchronizer extends AbstractResourceSynchronizer implements TestCenterSynchronizer
{
    /**
     * Get the synchronizer identifier
     *
     * @return string
     */
    public function getId()
    {
        return self::SYNC_TEST_CENTER;
    }

    /**
     * Get the root class of entity to synchronize
     *
     * @return \core_kernel_classes_Class
     */
    protected function getRootClass()
    {
        return $this->getClass(TestCenterService::CLASS_URI);
    }
}