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

namespace oat\taoSync\scripts\tool;

use oat\generis\model\OntologyRdfs;
use oat\taoOauth\model\bootstrap\OAuth2Type;
use oat\taoOauth\model\OAuthClient;
use oat\taoOauth\model\storage\ConsumerStorage;
use oat\taoOauth\scripts\tools\ImportConsumer;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;

class CreateOauthConsumer extends ImportConsumer
{
    protected function run()
    {
        $report = \common_report_Report::createInfo('Registering synchronisation consumer...');
        $report->add(parent::run());
        $this->createOauthEndpoint();
        $report->add(\common_report_Report::createSuccess('Endpoint successfully added.'));
        return $report;
    }

    protected function createOauthEndpoint()
    {
        $rootUrl = $this->getOption('rootUrl');
        $key = $this->getOption('key');
        $secret = $this->getOption('secret');
        $tokenUrl = $this->getOption('tokenUrl');

        PlatformService::singleton()->getRootClass()->createInstanceWithProperties(array(
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

    protected function provideUsage()
    {
        return [
            'prefix' => 'h',
            'longPrefix' => 'help',
            'description' => 'description',
        ];
    }

    protected function provideOptions()
    {
        $options = array_merge(parent::provideOptions(),
            array(
                'rootUrl' => [
                    'prefix' => 'u',
                    'longPrefix' => 'root-url',
                    'required' => true,
                    'description' => 'The endpoint of the synchronisation data with oauth consumer',
                ],
            )
        );

        return $options;
    }
}