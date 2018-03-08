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

namespace oat\taoSync\model\oauth;

use oat\generis\model\GenerisRdf;
use oat\taoOauth\model\user\UserService;

class OauthUserService extends UserService
{
    const TAO_SYNC_ROLE = 'http://www.tao.lu/Ontologies/generis.rdf#SyncManagerRole';

    /**
     * During oauthUser creation add role taoSyncRole
     *
     * @param \core_kernel_classes_Resource $consumer
     * @return \core_kernel_classes_Resource
     */
    public function createConsumerUser(\core_kernel_classes_Resource $consumer)
    {
        $consumer = parent::createOauthUser($consumer);
        $consumer->setPropertyValue($this->getProperty(GenerisRdf::PROPERTY_USER_ROLES), self::TAO_SYNC_ROLE);
        return $consumer;
    }

}