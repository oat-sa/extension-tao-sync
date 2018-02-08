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

namespace oat\taoSync\model\client;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\controller\ResultApi;
use oat\taoSync\controller\SynchronisationApi;
use oat\taoSync\scripts\tool\SynchronizeData;
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

    /**
     * Get list of remote entities associated to the given type
     * Parameters can be passed to remote
     *
     * @param $type
     * @param $params
     * @return mixed
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function fetchEntityChecksums($type, $params)
    {
        $url = '/taoSync/SynchronisationApi/fetchEntityChecksums?' . http_build_query([SynchronisationApi::PARAM_TYPE => $type, SynchronisationApi::PARAM_PARAMETERS => $params]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        if ($response->getStatusCode() != 200) {
            throw new \common_Exception('An error has occurred during calling remote server with message : ' . $response->getBody()->getContents());
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get list of remote properties with properties
     * Requested entity ids have to passed as 'entityIds' parameters
     *
     * @param $type
     * @param $entityIds
     * @return mixed
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function fetchEntityDetails($type, $entityIds)
    {
        $url = '/taoSync/SynchronisationApi/fetchEntityDetails?' . http_build_query([SynchronisationApi::PARAM_TYPE => $type, SynchronisationApi::PARAM_ENTITY_IDS => $entityIds]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        if ($response->getStatusCode() != 200) {
            throw new \common_Exception('An error has occurred during calling remote server with message : ' . $response->getBody()->getContents());
        }
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
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function getMissingClasses($type, array $classes)
    {
        $url = '/taoSync/SynchronisationApi/fetchClassDetails?' . http_build_query([SynchronisationApi::PARAM_TYPE => $type, SynchronisationApi::PARAM_REQUESTED_CLASSES => $classes]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        if ($response->getStatusCode() != 200) {
            throw new \common_Exception('An error has occurred during calling remote server with message : ' . $response->getBody()->getContents());
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get a remote test package as stream, associated to the delivery uri
     *
     * @param $deliveryUri
     * @return StreamInterface
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */

    public function getRemoteDeliveryTest($deliveryUri)
    {
        $url = '/taoSync/SynchronisationApi/getDeliveryTest?' . http_build_query([SynchronisationApi::PARAM_DELIVERY_URI => $deliveryUri]);
        $method = 'GET';

        $response = $this->call($url, $method);
        if ($response->getStatusCode() != 200) {
            throw new \common_Exception('An error has occurred during calling remote server with message : ' . $response->getBody()->getContents());
        }
        return $response->getBody();
    }

    /**
     * Allow to call directly an url
     *
     * Used to chain split requests with 'nextCallUrl' param on response
     * This params has the responsibility and should not be altered
     *
     * @param $url
     * @param string $method
     * @param null $body
     * @return mixed
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function callUrl($url, $method = 'GET', $body = null)
    {
        /** @var Response $response */
        $response = $this->call($url, $method, $body);
        if ($response->getStatusCode() != 200) {
            throw new \common_Exception('An error has occurred during calling remote server with message : ' . $response->getBody()->getContents());
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Send results to be synchronized to remote host
     *
     * @param array $results
     * @return mixed
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function sendResults(array $results)
    {
        $url = '/taoSync/ResultApi/syncResults';
        $method = 'POST';

        $response = $this->call($url, $method, json_encode([ResultApi::PARAM_RESULTS => $results]));
        if ($response->getStatusCode() != 200) {
            throw new \common_Exception('An error has occurred during calling remote server with message : ' . $response->getBody()->getContents());
        }
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Process an http call to a remote environment
     *
     * @param $url
     * @param string $method
     * @param null $body
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \common_Exception
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     * @throws \core_kernel_classes_EmptyProperty
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
                    $syncAction = preg_replace('/(\/|\\\\)+/', '\\', SynchronizeData::class);
                    if ($actionProperty == $syncAction) {
                        return $env;
                    }
                }
            }
        }
        throw new \common_exception_NotImplemented('No environment was associated to synchronisation. Process cancelled');
    }

}