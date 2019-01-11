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

use oat\oatbox\event\EventManager;
use oat\taoOauth\model\OauthController;
use oat\taoSync\model\event\SyncFinishedEvent;
use oat\taoSync\model\event\SyncRequestEvent;
use oat\taoSync\model\event\SyncResponseEvent;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;
use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizerService;
use oat\taoSync\model\SyncService;

/**
 * Class SynchronisationApi
 *
 * A host endpoint to answer incoming requests from a remote client to synchronize
 *
 * @package oat\taoSync\controller
 */
class SynchronisationApi extends \tao_actions_RestController
{
    const PARAM_TYPE = 'type';
    const PARAM_PARAMETERS = 'params';
    const PARAM_DELIVERY_URI = 'delivery-uri';
    const PARAM_REQUESTED_CLASSES = 'requested-classes';
    const PARAM_ENTITY_IDS = 'entityIds';

    /**
     * Fetch a set of entities based on 'params' parameter
     *
     * A 'type' parameter is required to scope the request
     * 'params' parameters is optional but useful to manage the request
     *
     * The response should contains a 'nextCallUrl' to create a chain leaded by the host
     *
     * @throws \common_exception_NotImplemented
     */
    public function fetchEntityChecksums()
    {
        try {
            $this->assertHttpMethod(\Request::HTTP_GET);

            if (!$this->hasRequestParameter(self::PARAM_TYPE)) {
                throw new \InvalidArgumentException('A valid "' . self::PARAM_TYPE . '" parameter is required to access ' . __FUNCTION__);
            }

            $type = $this->getRequestParameter(self::PARAM_TYPE);
            $params = $this->hasRequestParameter(self::PARAM_PARAMETERS) ? $this->getRequestParameter(self::PARAM_PARAMETERS) : [];

            $this->returnJson($this->getSyncService()->fetch($type, $params));

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Fetch entity properties for all entity id in 'entityIds' parameters
     *
     * A 'type' parameter is required to scope the request
     *
     * @throws \common_exception_NotImplemented
     */
    public function fetchEntityDetails()
    {
        $report = \common_report_Report::createInfo('Synchronization request received.');
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $params = [];

        try {
            $this->assertHttpMethod(\Request::HTTP_POST);

            $parameters = $this->getInputParameters();

            if (!isset($parameters[self::PARAM_TYPE])) {
                throw new \InvalidArgumentException('A valid "' . self::PARAM_TYPE . '" parameter is required to access ' . __FUNCTION__);
            }

            if (!isset($parameters[self::PARAM_ENTITY_IDS])) {
                throw new \InvalidArgumentException('Missing "' . self::PARAM_ENTITY_IDS . '" parameter to process ' . __FUNCTION__);
            }

            $type = $parameters[self::PARAM_TYPE];
            $entityIds = $parameters[self::PARAM_ENTITY_IDS];
            $entityIds = is_array($entityIds) ? $entityIds : [$entityIds];
            if (isset($parameters[self::PARAM_PARAMETERS])) {
                $params = $parameters[self::PARAM_PARAMETERS];
            }

            $eventManager->trigger(new SyncRequestEvent($params, $report));

            $entities = $this->getSyncService()->fetchEntityDetails($type, $entityIds, $params);

            $report->setData($logData = [$type => ['pushed' => count($entities)]]);
            $report->add(\common_report_Report::createInfo(sprintf('(%s) %d entities pushed.', $type, count($entities))));
            $eventManager->trigger(new SyncResponseEvent($params, $report));

            $this->returnJson($entities);
        } catch (\Exception $e) {
            $report->add(\common_report_Report::createFailure('Synchronization request failed: ' . $e->getMessage()));
            $eventManager->trigger(new SyncResponseEvent($params, $report));

            $this->returnFailure($e);
        }
    }

    /**
     * Endpoint to recreate resource classes tree
     *
     * Based on given 'requestedClasses' parameter, a tree of classes is returned
     *
     * @throws \common_exception_NotImplemented
     */
    public function fetchClassDetails()
    {
        try {
            $this->assertHttpMethod(\Request::HTTP_POST);

            $parameters = $this->getInputParameters();

            if (!isset($parameters[self::PARAM_TYPE])) {
                throw new \InvalidArgumentException('A valid "' . self::PARAM_TYPE . '" parameter is required to access ' . __FUNCTION__);
            }
            $type = $parameters[self::PARAM_TYPE];

            if (!$parameters[self::PARAM_REQUESTED_CLASSES]) {
                return $this->returnFailure(new \common_Exception('No requested class provided.'));
            }

            $requestedClasses = $parameters[self::PARAM_REQUESTED_CLASSES];
            if (!is_array($requestedClasses)) {
                return $this->returnFailure(new \common_Exception('Requested classes is malformed.'));
            }

            $this->returnJson($this->getSyncService()->fetchMissingClasses($type, $requestedClasses));

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Get a stream of Qti test package
     *
     * Based on incoming 'delivery-uri', the associated test package is returned
     *
     * @throws \common_exception_NotImplemented
     */
    public function getDeliveryTest()
    {
        try {
            $this->assertHttpMethod(\Request::HTTP_GET);

            if (!$this->hasRequestParameter(self::PARAM_DELIVERY_URI)) {
                throw new \InvalidArgumentException('A valid "' . self::PARAM_DELIVERY_URI . '" parameter is required for ' . __METHOD__ .'.');
            }

            $deliveryPackage = $this->getDeliverySynchronisationService()->getDeliveryTestPackage(
                $this->getResource($this->getRequestParameter(self::PARAM_DELIVERY_URI))
            );

            $body = $deliveryPackage['testPackage']->readPsrStream();
            \tao_helpers_Http::returnStream($body);

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * Receive confirmation message from client that synchronization finished.
     */
    public function confirmSyncFinished()
    {
        $syncParams = [];
        $report = \common_report_Report::createInfo('Synchronization finished.');
        try {
            $this->assertHttpMethod(\Request::HTTP_POST);
            $parameters = $this->getInputParameters();

            if (isset($parameters[self::PARAM_PARAMETERS])) {
                $syncParams = $parameters[self::PARAM_PARAMETERS];
            }

            $this->returnJson(['message' => 'Confirmation received.']);
        } catch (\Exception $e) {
            $this->returnFailure($e);
        } finally {
            $this->getServiceLocator()->get(EventManager::SERVICE_ID)->trigger(
                new SyncFinishedEvent($syncParams, $report)
            );
        }
    }

    /**
     * Get the input parameters as array
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getInputParameters()
    {
        $parameters = file_get_contents('php://input');
        if (!is_array($parameters = json_decode($parameters, true)) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Missing parameters to access ' . __FUNCTION__);
        }
        return $parameters;
    }

    /**
     * Throws a exception if http method is not the given $method
     *
     * @param $method
     * @throws \BadMethodCallException
     */
    protected function assertHttpMethod($method)
    {
        if ($this->getRequestMethod() != $method) {
            throw new \BadMethodCallException('Only ' . $method . ' method is accepted to access this functionality.');
        }
    }

    /**
     * @return DeliverySynchronizerService
     */
    protected function getDeliverySynchronisationService()
    {
        return $this->getServiceLocator()->get(DeliverySynchronizerService::SERVICE_ID);
    }

    /**
     * @return SyncService
     */
    protected function getSyncService()
    {
        return $this->getServiceLocator()->get(SyncService::SERVICE_ID);
    }

}