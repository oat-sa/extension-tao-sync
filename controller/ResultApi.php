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

use oat\taoSync\model\DeliveryLog\SyncDeliveryLogServiceInterface;
use oat\taoSync\model\ResultService;
use oat\taoSync\model\TestSession\SyncTestSessionServiceInterface;

/**
 * Class ResultApi
 *
 * @package oat\taoSync\controller
 */
class ResultApi extends \tao_actions_RestController
{
    const PARAM_RESULTS = 'results';

    const PARAM_DELIVERY_LOGS = 'delivery_logs';

    const PARAM_TEST_SESSIONS = 'test_sessions';

    /**
     * Api endpoint to receive results
     * An acknowledgment list is returned to confirm which results are imported
     *
     * @throws \common_exception_NotImplemented
     */
    public function syncResults()
    {
        try {
            if ($this->getRequestMethod() != \Request::HTTP_POST) {
                throw new \BadMethodCallException('Only POST method is accepted to access ' . __FUNCTION__);
            }

            $parameters = file_get_contents('php://input');
            if (
                is_array($parameters = json_decode($parameters, true))
                && json_last_error() === JSON_ERROR_NONE
                && isset($parameters[self::PARAM_RESULTS])
                && is_array($parameters[self::PARAM_RESULTS])
            ) {
                $results = $parameters[self::PARAM_RESULTS];
            } else {
                throw new \InvalidArgumentException('A valid "' . self::PARAM_RESULTS . '" parameter is required to access ' . __FUNCTION__);
            }

            $response = $this->getSyncResultService()->importDeliveryResults($results);
            $this->returnJson($response);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * @throws \common_exception_NotImplemented
     */
    public function syncDeliveryLogs()
    {
        try {
            if ($this->getRequestMethod() != \Request::HTTP_POST) {
                throw new \BadMethodCallException('Only POST method is accepted to access ' . __FUNCTION__);
            }

            $parameters = file_get_contents('php://input');
            if (
                is_array($parameters = json_decode($parameters, true))
                && json_last_error() === JSON_ERROR_NONE
                && isset($parameters[self::PARAM_DELIVERY_LOGS])
                && is_array($parameters[self::PARAM_DELIVERY_LOGS])
            ) {
                $logs = $parameters[self::PARAM_DELIVERY_LOGS];
            } else {
                throw new \InvalidArgumentException('A valid "' . self::PARAM_DELIVERY_LOGS . '" parameter is required to access ' . __FUNCTION__);
            }

            $response = $this->getSyncResultLogService()->importDeliveryLogs($logs);

            $this->returnJson($response);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * @throws \common_exception_NotImplemented
     */
    public function syncTestSessions()
    {
        try {
            if ($this->getRequestMethod() != \Request::HTTP_POST) {
                throw new \BadMethodCallException('Only POST method is accepted to access ' . __FUNCTION__);
            }

            $parameters = file_get_contents('php://input');
            if (
                is_array($parameters = json_decode($parameters, true))
                && json_last_error() === JSON_ERROR_NONE
                && isset($parameters[self::PARAM_TEST_SESSIONS])
                && is_array($parameters[self::PARAM_TEST_SESSIONS])
            ) {
                $sessions = $parameters[self::PARAM_TEST_SESSIONS];
            } else {
                throw new \InvalidArgumentException('A valid "' . self::PARAM_TEST_SESSIONS . '" parameter is required to access ' . __FUNCTION__);
            }

            $response = $this->getSyncTestSessionsService()->importTestSessions($sessions);

            $this->returnJson($response);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * @return array|ResultService|object
     */
    protected function getSyncResultService()
    {
        return $this->getServiceLocator()->get(ResultService::SERVICE_ID);
    }

    /**
     * @return array|SyncDeliveryLogServiceInterface|object
     */
    protected function getSyncResultLogService()
    {
        return $this->getServiceLocator()->get(SyncDeliveryLogServiceInterface::SERVICE_ID);
    }

    /**
     * @return array|SyncTestSessionServiceInterface|object
     */
    protected function getSyncTestSessionsService()
    {
        return $this->getServiceLocator()->get(SyncTestSessionServiceInterface::SERVICE_ID);
    }

}