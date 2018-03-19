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

namespace oat\taoSync\model\server;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ServiceManager;
use oat\tao\model\oauth\DataStore;
use oat\taoOauth\model\storage\ConsumerStorage;
use oat\taoSync\model\formatter\FormatterService;
use oat\taoSync\model\SyncService;

class HandShakeServerResponse
{
    use OntologyAwareTrait;

    /** @var \core_kernel_classes_Resource */
    protected $consumerOauth;

    /** @var \core_kernel_classes_Resource */
    protected $user;

    /**
     * HandShakeServerResponse constructor.
     * @param \core_kernel_classes_Resource $consumerOauth
     */
    public function __construct(\core_kernel_classes_Resource $consumerOauth, \core_kernel_classes_Resource $user)
    {
        $this->consumerOauth = $consumerOauth;
        $this->user  = $user;
    }

    /**
     * @return array
     * @throws \common_Exception
     * @throws \core_kernel_classes_EmptyProperty
     */
    public function asArray()
    {
        /** @var SyncService $syncService */
        $syncService = ServiceManager::getServiceManager()->get(SyncService::SERVICE_ID);
        $synchronizer = $syncService->getSynchronizer('administrator');
        $formatter = $synchronizer->getFormatter();

        return [
            'syncUser' => $formatter->format($this->user, [FormatterService::OPTION_INCLUDED_PROPERTIES => true]),
            'oauthInfo' => [
                'organizationId' => 789,
                'key' => $this->consumerOauth->getUniquePropertyValue($this->getProperty(DataStore::PROPERTY_OAUTH_KEY))->literal,
                'secret' => $this->consumerOauth->getUniquePropertyValue($this->getProperty(DataStore::PROPERTY_OAUTH_SECRET))->literal,
                'tokenUrl' => $this->consumerOauth->getUniquePropertyValue($this->getProperty(ConsumerStorage::CONSUMER_TOKEN_URL))->literal,
            ]
        ];
    }
}