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

use oat\oatbox\user\LoginService;
use oat\oatbox\user\LoginFailedException;
use oat\taoSync\model\server\HandShakeServerService;

class HandShake extends \tao_actions_CommonModule
{
    use \tao_actions_RestTrait;

    const USER_IDENTIFIER = 'login';
    const USER_PASSWORD   = 'password';

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

        header('Content-Type: ' . $this->responseEncoding);
    }

    /**
     * Authenticate the syncManager user and get his data along with the Sync HandShake
     * The request must be a POST, and it's body is application/json
     * that contains the credentials as :  { login : login, password : password }.
     * -> 200 whith the handshake
     * -> 401 for wrong authentication
     * -> 412 for malformed requests (missing parameter)
     * -> 500 otherwise
     *
     */
    public function index()
    {
        //allow preflight requests
        if ($this->getRequest()->isOptions()) {
            header('HTTP/1.0 200 OK');
            return;
        }

        try {
            if (!$this->getRequest()->isPost()) {
                throw new \BadMethodCallException('Only POST method is accepted to access ' . __FUNCTION__);
            }

            $parameters = file_get_contents('php://input');

            if (
                is_array($parameters = json_decode($parameters, true))
                && json_last_error() === JSON_ERROR_NONE
                && isset($parameters[self::USER_IDENTIFIER])
                && isset($parameters[self::USER_PASSWORD])
            ) {
                $userIdentifier = $parameters[self::USER_IDENTIFIER];
                $userPassword   = $parameters[self::USER_PASSWORD];
            } else {
                return $this->returnJson([
                    'success'   => false,
                    'errorCode' => '412',
                    'errorMsg'  => 'Valid "' . self::USER_IDENTIFIER . '" and "' . self::USER_PASSWORD . '" parameters are required to access ' . __FUNCTION__,
                    'version'   => TAO_VERSION
                ], 412);
            }

            //check identity
            if (!$this->isAllowedUser($userIdentifier, $userPassword)) {
                return $this->returnJson([
                    'success'   => false,
                    'errorCode' => '401',
                    'errorMsg'  => 'You are not authorized to access this functionality.',
                    'version'   => TAO_VERSION
                ], 401);
            }


            /** @var HandShakeServerService $handShakeServer */
            $handShakeServer = $this->getServiceLocator()->get(HandShakeServerService::SERVICE_ID);
            $response = $handShakeServer->execute($userIdentifier);

            $this->returnJson($response->asArray());
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Check whether the given credentials match an authorized user
     * @param string $userIdentifier the login of the user
     * @param string $userPassword   the password of the user
     * @return boolean true if allowed
     */
    protected function isAllowedUser($userIdentifier, $userPassword)
    {
        try {
            return LoginService::authenticate($userIdentifier, $userPassword);
        } catch (LoginFailedException $e) {
            return false;
        }
    }
}
