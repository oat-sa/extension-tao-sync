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
use Psr\Http\Message\ResponseInterface;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoSync\controller\ResultApi;
use oat\taoSync\controller\SynchronisationApi;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;
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
     */
    public function fetchEntityChecksums($type, $params)
    {
        $url = '/taoSync/SynchronisationApi/fetchEntityChecksums?' . http_build_query([
            SynchronisationApi::PARAM_TYPE => $type,
            SynchronisationApi::PARAM_PARAMETERS => $params,
        ]);
        $method = 'GET';

        /** @var Response $response */
        $response = $this->call($url, $method);
        return $this->decodeResponseBody($response);
    }

    /**
     * Get list of remote properties with properties
     * Requested entity ids have to passed as 'entityIds' parameters
     *
     * @param $type
     * @param $entityIds
     * @param array $params
     * @return mixed
     * @throws \common_Exception
     */
    public function fetchEntityDetails($type, $entityIds, array $params = [])
    {
        $url = '/taoSync/SynchronisationApi/fetchEntityDetails';
        $method = 'POST';

        $response = $this->call($url, $method, json_encode([
            SynchronisationApi::PARAM_TYPE => $type,
            SynchronisationApi::PARAM_ENTITY_IDS => $entityIds,
            SynchronisationApi::PARAM_PARAMETERS => $params,
        ]));

        return $this->decodeResponseBody($response);
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
     */
    public function getMissingClasses($type, array $classes)
    {
        $url = '/taoSync/SynchronisationApi/fetchClassDetails';
        $method = 'POST';

        $response = $this->call($url, $method, json_encode([
            SynchronisationApi::PARAM_TYPE => $type,
            SynchronisationApi::PARAM_REQUESTED_CLASSES => $classes
        ]));

        return $this->decodeResponseBody($response);
    }

    /**
     * Get a remote test package as stream, associated to the delivery uri
     *
     * @param $deliveryUri
     * @return StreamInterface
     * @throws \common_Exception
     */

    public function getRemoteDeliveryTest($deliveryUri)
    {
        $url = '/taoSync/SynchronisationApi/getDeliveryTest?' . http_build_query([
            SynchronisationApi::PARAM_DELIVERY_URI => $deliveryUri
        ]);
        $method = 'GET';

        $response = $this->call($url, $method);
        if (!preg_match('/2\d\d/', (string) $response->getStatusCode())) {
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
     */
    public function callUrl($url, $method = 'GET', $body = null)
    {
        /** @var Response $response */
        $response = $this->call($url, $method, $body);
        return $this->decodeResponseBody($response);
    }

    /**
     * Send results to be synchronized to remote host
     *
     * @param array $results
     * @param array $params Synchronization parameters
     * @return mixed
     * @throws \common_Exception
     */
    public function sendResults(array $results, array $params)
    {
        $url = '/taoSync/ResultApi/syncResults';
        $method = 'POST';

        $requestData = [
            ResultApi::PARAM_RESULTS => $results,
            ResultApi::PARAM_SYNC_PARAMETERS => $params
        ];
        $response = $this->call($url, $method, json_encode($requestData));
        return $this->decodeResponseBody($response);
    }

    /**
     * @param array $logs
     * @param array $params
     * @return mixed
     * @throws \common_Exception
     */
    public function sendDeliveryLogs(array $logs, array $params)
    {
        $url = '/taoSync/ResultApi/syncDeliveryLogs';
        $method = 'POST';

        $requestData = [
            ResultApi::PARAM_DELIVERY_LOGS => $logs,
            ResultApi::PARAM_SYNC_PARAMETERS => $params
        ];
        $response = $this->call($url, $method, json_encode($requestData));
        return $this->decodeResponseBody($response);
    }

    /**
     * @param array $users
     * @param array $params Synchronization parameters
     * @return mixed
     * @throws \common_Exception
     */
    public function sendLtiUsers(array $users, array $params)
    {
        $url = '/taoSync/ResultApi/syncLtiUsers';
        $method = 'POST';

        $requestData = [
            ResultApi::PARAM_LTI_USERS => $users,
            ResultApi::PARAM_SYNC_PARAMETERS => $params
        ];
        $response = $this->call($url, $method, json_encode($requestData));
        return $this->decodeResponseBody($response);
    }

    /**
     * @param array $sessions
     * @param array $params Synchronization params
     * @return mixed
     * @throws \common_Exception
     */
    public function sendTestSessions(array $sessions, array $params)
    {
        $url = '/taoSync/ResultApi/syncTestSessions';
        $method = 'POST';

        $requestData = [
            ResultApi::PARAM_TEST_SESSIONS => $sessions,
            ResultApi::PARAM_SYNC_PARAMETERS => $params
        ];
        $response = $this->call($url, $method, json_encode($requestData));
        return $this->decodeResponseBody($response);
    }

    /**
     * Send confirmation to the central server about completed synchronization on the client.
     *
     * @param array $syncParams
     * @param array $clientState
     * @return array
     * @throws \common_Exception
     */
    public function sendSyncFinishedConfirmation(array $syncParams, $clientState)
    {
        $url = '/taoSync/SynchronisationApi/confirmSyncFinished';
        $method = \Request::HTTP_POST;
        $requestData = [
            SynchronisationApi::PARAM_PARAMETERS => $syncParams,
            SynchronisationApi::PARAM_CLIENT_STATE => $clientState,
        ];
        $response = $this->call($url, $method, json_encode($requestData));

        return $this->decodeResponseBody($response);
    }

    /**
     * json_decode the body content of given response
     *
     * Parse error to have a readable message if http code is not 2**
     *
     * @param ResponseInterface $response
     * @return mixed
     * @throws \common_Exception
     */
    protected function decodeResponseBody(ResponseInterface $response)
    {
        if (!preg_match('/2\d\d/', (string) $response->getStatusCode())) {
            $message = 'An error has occurred during calling synchronisation server. Http code : ' . $response->getStatusCode() . '. ';
            $body = json_decode($response->getBody()->getContents(), true);
            if (JSON_ERROR_NONE === json_last_error() && isset($body['errorMsg']) && !empty($body['errorMsg'])) {
                $message .= 'Message : ' . $body['errorMsg'];
            }
            throw new \common_Exception($message);
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

        try {
            return $this->getServiceLocator()->get(PublishingService::SERVICE_ID)->callEnvironment(SynchronizeData::class, $request);
        } catch (\Exception $e) {
            throw new \common_Exception($e->getMessage(), 0, $e);
        }
    }

}
