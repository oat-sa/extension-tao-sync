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

namespace oat\taoSync\controller;

use oat\oatbox\user\LoginFailedException;
use oat\taoSync\model\server\HandShakeServerService;

class HandShake extends \tao_actions_CommonModule
{
    use \tao_actions_RestTrait;

    const USER_IDENTIFIER = 'login';

    /**
     * Check response encoding requested
     *
     * tao_actions_RestModule constructor.
     */
    public function __construct()
    {
        if ($this->hasHeader("Accept")) {
            try {
                $this->responseEncoding = (\tao_helpers_Http::acceptHeader($this->getAcceptableMimeTypes(), $this->getHeader("Accept")));
            } catch (\common_exception_ClientException $e) {
                $this->returnFailure($e);
            }
        }

        header('Content-Type: '.$this->responseEncoding);
    }

    public function index()
    {
        if (!$this->isAllowedUser()) {
            $data['success']	= false;
            $data['errorCode']	= '401';
            $data['errorMsg']	= 'You are not authorized to access this functionality.';
            $data['version']	= TAO_VERSION;

            header('HTTP/1.0 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="' . GENERIS_INSTANCE_NAME . '"');
            echo json_encode($data);
            return;
        }

        try {
            if ($this->getRequestMethod() != \Request::HTTP_POST) {
                throw new \BadMethodCallException('Only POST method is accepted to access ' . __FUNCTION__);
            }

            $parameters = file_get_contents('php://input');

            if (is_array($parameters = json_decode($parameters, true))
                && json_last_error() === JSON_ERROR_NONE
                && isset($parameters[self::USER_IDENTIFIER])
            ) {
                $userIdentifier = $parameters[self::USER_IDENTIFIER];
            } else {
                throw new \InvalidArgumentException('A valid "' . self::USER_IDENTIFIER . '" parameter is required to access ' . __FUNCTION__);
            }

            /** @var HandShakeServerService $handShakeServer */
            $handShakeServer = $this->getServiceLocator()->get(HandShakeServerService::SERVICE_ID);
            $response = $handShakeServer->execute($userIdentifier);

            $this->returnJson($response->asArray());
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    protected function isAllowedUser()
    {
        try {
            $request = \common_http_Request::currentRequest();
            $authAdapter = new \tao_models_classes_HttpBasicAuthAdapter($request);
            $authAdapter->authenticate();
            return true;
        } catch (LoginFailedException $e) {
            return false;
        }
    }
}