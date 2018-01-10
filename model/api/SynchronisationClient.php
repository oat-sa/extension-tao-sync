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

    public function fetchRemoteEntities($type, $limit=100, $offset=0)
    {
        $url = '/taoSync/SynchronisationApi/fetch?' . http_build_query(['type' => $type, 'limit' => $limit, 'offset' => $offset]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function count($type)
    {
        $url = '/taoSync/SynchronisationApi/count?' . http_build_query(['type' => $type]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function getMissingClasses($type, array $classes)
    {
        $url = '/taoSync/SynchronisationApi/fetchClassDetails?' . http_build_query(['type' => $type, 'requestedClasses' => $classes]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        return json_decode($response->getBody()->getContents(), true);
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

            foreach ($actionProperties as $actionProperty) {
                if ($actionProperty) {
                    $actionProperty = preg_replace('/(\/|\\\\)+/', '\\', $actionProperty);
                    $syncAction = preg_replace('/(\/|\\\\)+/', '\\', SyncDeliveryData::class);
                    if ($actionProperty == $syncAction) {
                        return $env;
                    }
                }
            }
        }
        throw new \common_exception_NotImplemented('No environment was associated to synchronisation. Process cancelled');
    }

}