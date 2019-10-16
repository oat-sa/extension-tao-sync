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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoSync\controller;
use oat\taoSync\model\tusUpload\TusUploadServerService;

abstract class TusUpload extends \tao_actions_RestController
{

    /**
     * Main entry point for all requests.
     */
    public function index()
    {
        /** @var TusUploadServerService $tusUploadService */
        $tusUploadService = $this->getServiceLocator()->get(TusUploadServerService::SERVICE_ID);
        $responseData = $tusUploadService->serve($this->getPsrRequest());

        if ($responseData['uploadComplete']) {
            $this->completeAction($responseData);
        }
        $this->sendResponse($responseData['content'], $responseData['status'], $responseData['headers']);
        exit(0);
    }

    /**
     *
     * @param $content
     * @param int $status
     * @param array $headers
     */
    protected function sendResponse($content, $httpStatus = 200, $headers = [])
    {
        header(HTTPToolkit::statusCodeHeader($httpStatus));
        foreach ($headers as $key => $value) {
            header($key . ': ' . $value);
        }
        if (is_array($content)) {
            Context::getInstance()->getResponse()->setContentHeader('application/json');
            echo json_encode($content);
        }
    }

    /**
     *  Additional method to implement TUS protocol in TAO.
     *  Generate and send unique key that will be used instead of `path/filename`.
     *
     */
    public function getKey()
    {
        //not implemented
    }

    /**
     * @param array $responseData
     * @return mixed
     */
    abstract protected function completeAction($responseData);
}
