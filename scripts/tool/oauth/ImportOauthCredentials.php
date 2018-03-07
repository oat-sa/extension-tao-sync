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

use oat\generis\model\OntologyRdfs;
use oat\taoOauth\model\bootstrap\OAuth2Type;
use oat\taoOauth\model\OAuthClient;
use oat\taoOauth\model\storage\ConsumerStorage;
use oat\taoOauth\scripts\tools\ImportConsumer;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\model\SyncService;

class ImportOauthCredentials extends ImportConsumer
{
    /**
     * Run the script
     *
     * Create consumer for oauth authentication
     * Add taoSync role to allow consumer to connect to taoSync API
     * Create an environment to link the consumer to an endpoint
     *
     * @return \common_report_Report
     * @throws \common_exception_Error
     * @throws \core_kernel_users_Exception
     */
    protected function run()
    {
        $report = \common_report_Report::createInfo('Registering synchronisation consumer...');
        $report->add(parent::run());
        $this->addUserRoles($this->createdConsumer);
        $this->createOauthEndpoint();
        $report->add(\common_report_Report::createSuccess('Endpoint successfully added.'));
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

    /**
     * Create an environment to link consumer to root url endpoint
     *
     * @return \core_kernel_classes_Resource
     */
    protected function createOauthEndpoint()
    {
        $rootUrl = $this->getOption('rootUrl');
        $key = $this->getOption('key');
        $secret = $this->getOption('secret');
        $tokenUrl = $this->getOption('tokenUrl');

        return PlatformService::singleton()->getRootClass()->createInstanceWithProperties(array(
            OntologyRdfs::RDFS_LABEL => 'Synchronization client',
            PlatformService::PROPERTY_AUTH_TYPE => (new OAuth2Type())->getAuthClass()->getUri(),
            PublishingService::PUBLISH_ACTIONS => 'oat\\\\taoSync\\\\scripts\\\\tool\\\\SynchronizeData',
            PlatformService::PROPERTY_ROOT_URL => $rootUrl,
            ConsumerStorage::CONSUMER_CLIENT_KEY => $key,
            ConsumerStorage::CONSUMER_CLIENT_SECRET => $secret,
            ConsumerStorage::CONSUMER_TOKEN_URL => $tokenUrl,
            ConsumerStorage::CONSUMER_TOKEN_TYPE => OAuthClient::DEFAULT_TOKEN_TYPE,
            ConsumerStorage::CONSUMER_TOKEN_GRANT_TYPE => OAuthClient::DEFAULT_GRANT_TYPE,
        ));
    }

}