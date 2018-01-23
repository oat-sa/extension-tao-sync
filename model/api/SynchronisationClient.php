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

namespace oat\taoSync\model\api;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\controller\SynchronisationApi;
use oat\taoSync\scripts\tool\SyncDeliveryData;
use \Psr\Http\Message\StreamInterface;

/**
 * Class SynchronisationClient
 *
 * The Http client to send request to synchronisation
 *
 * @package oat\taoSync\model\api
 */
class SynchronisationClient extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/client';

    public function fetch($type, $options)
    {
        $url = '/taoSync/SynchronisationApi/fetch?' . http_build_query(['type' => $type, SynchronisationApi::PARAMS => $options]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get list of remote entities associated to the given type
     * Option parameters can be passed to remote
     *
     * @param $type
     * @param $options
     * @return mixed
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function fetchRemoteEntities($type, $options)
    {
        $url = '/taoSync/SynchronisationApi/fetch?' . http_build_query(['type' => $type, SynchronisationApi::PARAMS => $options]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get count remote entities associated to the given type
     * Option parameters can be passed to remote
     *
     * @param $type
     * @param $options
     * @return mixed
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function count($type, $options)
    {
        $url = '/taoSync/SynchronisationApi/count?' . http_build_query(['type' => $type, SynchronisationApi::PARAMS => $options]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get the classes locally missing
     *
     * Classes are scoped to the $type
     * Option parameters can be passed to remote
     *
     * @param $type
     * @param array $classes
     * @return mixed
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function getMissingClasses($type, array $classes)
    {
        $url = '/taoSync/SynchronisationApi/fetchClassDetails?' . http_build_query(['type' => $type, 'requestedClasses' => $classes]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get a remote test package as stream, associated to the delivery uri
     *
     * @param $deliveryUri
     * @return StreamInterface
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */

    public function getRemoteDeliveryTest($deliveryUri)
    {
        $url = '/taoSync/SynchronisationApi/getDeliveryTest?' . http_build_query(['uri' => $deliveryUri]);
        $method = 'GET';

        return $this->call($url, $method)->getBody();
    }

    /**
     * Process an http call to a remote environment
     *
     * @param $url
     * @param string $method
     * @param null $body
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    protected function call($url, $method = 'GET', $body = null)
    {
        $request = new Request($method, $url);
        if (!is_null($body)) {
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

    /**
     * Get the remote environment to process the synchronisation
     *
     * @return \core_kernel_classes_Resource
     * @throws \common_exception_NotFound If no environment has been set
     * @throws \common_exception_NotImplemented If action is not set in environment
     */
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