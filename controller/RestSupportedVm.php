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

namespace oat\taoSync\controller;

use oat\taoSync\model\supportedVm\SupportedVmService;
use oat\taoTestCenter\controller\AbstractRestController;

/**
 * Class RestSupportedVm
 * @package oat\taoSync\controller
 *
 * @OA\Info(title="TAO Supported list of VM versions API", version="0.1")
 */
class RestSupportedVm extends AbstractRestController
{
    /** @var SupportedVmService */
    protected $service;

    /**
     * @throws \common_exception_RestApi
     */
    public function post()
    {
        throw new \common_exception_RestApi('Not implemented.');
    }

    /**
     * @OA\Get(
     *     path="/taoSync/api/supportedVm",
     *     tags={"supported VM version"},
     *     summary="Get list of supported TAO VM versions",
     *     description="Get list of supported TAO VM versions",
     *     @OA\Response(
     *         response="200",
     *         description="Supported TAO VM versions",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 example={
     *                     "success": true,
     *                     "data": {
     *                          "3.3.0-sprint97",
     *                          "3.3.0-sprint98",
     *                     },
     *                 }
     *             )
     *         ),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 example={
     *                     "success": false,
     *                     "errorCode": 0,
     *                     "errorMsg": "Can not return list of supported TAO VM versions",
     *                     "version": "3.3.0-sprint99"
     *                 }
     *             )
     *         ),
     *     ),
     * )
     */
    public function get()
    {
        try {
            $result = $this->getClassService()->getSupportedVmVersions();

            $this->returnJson([
                'success' => true,
                'data' => array_column($result, 'literal'),
            ]);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * @return SupportedVmService
     */
    protected function getClassService()
    {
        if ($this->service === null) {
            $this->service = SupportedVmService::singleton();
        }

        return $this->service;
    }
}
