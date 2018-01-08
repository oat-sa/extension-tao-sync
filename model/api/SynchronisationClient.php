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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model\api;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\scripts\tool\SyncDeliveryData;

class SynchronisationClient extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/client';

    public function getRemoteClassTree($classUri)
    {
        $url = '/taoSync/SynchronisationApi/getClassChecksum?class-uri= ' . urlencode($classUri);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    protected function call($url, $method = 'GET', $body = false)
    {
        $request = new Request($method, $url);
        if ($body) {
            if (is_array($body)) {
                $body = stream_for(http_build_query($body));
            } elseif (is_string($body)) {
                $body = stream_for($body);
            }
            $request = $request->withBody($body);
        }

        $request = $request->withHeader('Accept', 'application/json');
        $request = $request->withHeader('Content-type', 'application/json');

        $env = $this->getSyncEnvironment();
        return PlatformService::singleton()->callApi($env->getUri(), $request);
    }

    protected function getSyncEnvironment()
    {
        $environments = $this->getServiceLocator()->get(PublishingService::SERVICE_ID)->getEnvironments();

        if (empty($environments)) {
            throw new \common_exception_NotFound('No environment has been set.');
        }

        /** @var \core_kernel_classes_Resource $env */
        foreach ($environments as $env) {
            $property = $this->getProperty(PublishingService::PUBLISH_ACTIONS);
            $actionProperties = $env->getPropertyValues($property);
            if ($actionProperties && in_array(addslashes(SyncDeliveryData::class), $actionProperties)) {
                return $env;
            }
        }
        return false;
    }

}