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

use oat\taoSync\model\synchronizer\delivery\DeliverySynchronizerService;
use oat\taoSync\model\SyncService;

class SynchronisationApi extends \tao_actions_RestController
{
    const URI = 'uri';

    const CLASS_URI = 'class-uri';

    const TYPE = 'type';

    const PARAMS = 'params';

    public function fetch()
    {
        try {
            if ($this->getRequestMethod() != \Request::HTTP_GET) {
                throw new \BadMethodCallException('Only get method is accepted to access ' . __FUNCTION__);
            }

            if (!$this->hasRequestParameter(self::TYPE)) {
                throw new \InvalidArgumentException('A valid "' . self::TYPE . '" parameter is required to access ' . __FUNCTION__);
            }
            $type = $this->getRequestParameter(self::TYPE);
            $params = $this->hasRequestParameter(self::PARAMS) ? $this->getRequestParameter(self::PARAMS) : [];
            $entities = $this->getSyncService()->fetch($type, $params);

            $this->returnJson($entities);

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    public function fetchClassDetails()
    {
        try {
            if ($this->getRequestMethod() != \Request::HTTP_GET) {
                throw new \BadMethodCallException('Only get method is accepted to access ' . __FUNCTION__);
            }

            if (!$this->hasRequestParameter(self::TYPE)) {
                throw new \InvalidArgumentException('A valid "' . self::TYPE . '" parameter is required to access ' . __FUNCTION__);
            }
            $type = $this->getRequestParameter(self::TYPE);

            if (!$this->hasRequestParameter('requestedClasses')) {
                return $this->returnJson('No requested class provided.');
            }

            $requestedClasses = $this->getRequestParameter('requestedClasses');
            if (!is_array($requestedClasses)) {
                return $this->returnJson('Requested classes is malformed.');
            }

            $this->returnJson($this->getSyncService()->fetchMissingClasses($type, $requestedClasses));

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    public function getDeliveryTest()
    {
        try {
            // Check if it's post method
            if ($this->getRequestMethod() != \Request::HTTP_GET) {
                throw new \BadMethodCallException('Only get method is accepted for ' . __METHOD__ . '.');
            }

            if (!$this->hasRequestParameter(self::URI)) {
                throw new \InvalidArgumentException('A valid "' . self::URI . '" parameter is required for ' . __METHOD__ .'.');
            }

            $deliveryPackage = $this->getDeliverySynchronisationService()->getDeliveryTestPackage(
                $this->getResource($this->getRequestParameter(self::URI))
            );

            $body = $deliveryPackage['testPackage']->readPsrStream();
            \tao_helpers_Http::returnStream($body);

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    protected function getDeliverySynchronisationService()
    {
        return $this->propagate(new DeliverySynchronizerService());
    }

    /**
     * @return SyncService
     */
    protected function getSyncService()
    {
        return $this->getServiceLocator()->get(SyncService::SERVICE_ID);
    }

}