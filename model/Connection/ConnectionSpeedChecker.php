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

namespace oat\taoSync\model\Connection;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\client\SynchronisationClient;

class ConnectionSpeedChecker extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/ConnectionSpeedChecker';

    /**
     * @var SynchronisationClient
     */
    private $client;

    /**
     * Collect connection speed stats
     *
     * @throws \common_Exception
     */
    public function run()
    {
        $this->client = $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);

        $this->runUploadCheck();
        $this->runDownloadChecks();
    }

    /**
     * Upload test file to collect connection speed stats
     *
     * @throws \common_Exception
     */
    private function runUploadCheck()
    {
        $url = 'taoClientDiagnostic/CompatibilityChecker/upload';
        $body = [
            'upload' => str_repeat('a', 1024 * 1024)
        ];

        $this->client->callUrl($url, \common_http_Request::METHOD_POST, $body);
    }

    /**
     * Download test file to collect connection speed stats
     *
     * @throws \common_Exception
     */
    private function runDownloadChecks()
    {
        $url = 'taoClientDiagnostic/views/js/tools/bandwidth/data/bin1MB.data';
        $query = [time()];

        $this->client->callUrl($url, \common_http_Request::METHOD_GET, $query);
    }
}
