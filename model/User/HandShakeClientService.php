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

namespace oat\taoSync\model\User;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use function GuzzleHttp\Psr7\stream_for;
use oat\taoOauth\model\bootstrap\OAuth2Type;
use oat\taoOauth\model\OAuthClient;
use oat\taoOauth\model\storage\ConsumerStorage;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingService;

class HandShakeClientService extends ConfigurableService
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/handShake';

    const OPTION_ROOT_URL = 'rootURL';
    const OPTION_REMOTE_AUTH_URL = 'remoteAuthURL';

    /**
     * @param HandShakeClientRequest $handShakeRequest
     * @return bool|\core_kernel_classes_Resource
     * @throws \Exception
     */
    public function execute(HandShakeClientRequest $handShakeRequest)
    {
        $client = new Client();
        $request = new Request('POST',
            $this->getOption(static::OPTION_REMOTE_AUTH_URL)
        );

        $body = stream_for(json_encode(['login' => $handShakeRequest->getLogin()]));

        $request = $request->withHeader('Accept', 'application/json');
        $request = $request->withHeader('Content-type', 'application/json');
        $request = $request->withBody($body);

        $response = $client->send($request, [
            'auth' => array_values($handShakeRequest->toArray()), 'verify' => false,
        ]);

        if ($response->getStatusCode() == 500) {
            throw new \Exception('A internal error has occurred during server request.');
        }

        $responseParsed = json_decode($response->getBody()->getContents(), true);

        if (!isset($responseParsed['oauthInfo'])
            || !isset($responseParsed['syncUser'])
            || count(array_diff(['key', 'secret', 'tokenUrl'], array_keys($responseParsed['oauthInfo']))) !== 0
        ) {

            $this->logError('Oauth information not received');
            return false;
        }

        $oauthData = $responseParsed['oauthInfo'];
        $syncUser = $responseParsed['syncUser'];

        $key = $oauthData['key'];
        $secret = $oauthData['secret'];
        $tokenUrl = $oauthData['tokenUrl'];
        $action = 'oat\\\\taoSync\\\\scripts\\\\tool\\\\synchronisation\\\\SynchronizeData';

        /** @var PublishingService $publishingAuthService */
        $publishingAuthService = $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
        if ($founds = $publishingAuthService->findByAction($action)){
            foreach ($founds as $found){
                $found->delete();
            }
        }

        $inserted = PlatformService::singleton()->getRootClass()->createInstanceWithProperties(array(
            OntologyRdfs::RDFS_LABEL => 'Synchronization client',
            PlatformService::PROPERTY_AUTH_TYPE => (new OAuth2Type())->getAuthClass()->getUri(),
            PublishingService::PUBLISH_ACTIONS => 'oat\\\\taoSync\\\\scripts\\\\tool\\\\synchronisation\\\\SynchronizeData',
            PlatformService::PROPERTY_ROOT_URL => $this->getOption(static::OPTION_ROOT_URL),
            ConsumerStorage::CONSUMER_CLIENT_KEY => $key,
            ConsumerStorage::CONSUMER_CLIENT_SECRET => $secret,
            ConsumerStorage::CONSUMER_TOKEN_URL => $tokenUrl,
            ConsumerStorage::CONSUMER_TOKEN_TYPE => OAuthClient::DEFAULT_TOKEN_TYPE,
            ConsumerStorage::CONSUMER_TOKEN_GRANT_TYPE => OAuthClient::DEFAULT_GRANT_TYPE,
        ));

        $properties = isset($syncUser['properties']) ? $syncUser['properties'] : [];
        if (isset($properties[OntologyRdf::RDF_TYPE])) {
            $class = $this->getClass($properties[OntologyRdf::RDF_TYPE]);
        } else {
            $class = $this->getRootClass();
        }

        $resource = $this->getResource($syncUser['id']);
        $resource->setType($class);
        $resource->setPropertiesValues($properties);

        return true;
    }
}