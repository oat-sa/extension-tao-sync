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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\controller;

use oat\taoSync\model\SyncService;

class SynchronisationApi extends \tao_actions_RestController
{
    const CLASS_URI = 'class-uri';

    const TYPE = 'type';
    const LIMIT = 'limit';
    const OFFSET = 'offset';

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

            $limit = $this->hasRequestParameter(self::LIMIT) ? $this->getRequestParameter(self::LIMIT) : 100;
            $offset = $this->hasRequestParameter(self::OFFSET) ? $this->getRequestParameter(self::OFFSET) : 0;

            $filters = array();
            $options = array();
            $organistationId = '123456';
            $entities = $this->getSyncService()->fetch($type, $options, $filters);

            $this->returnJson($entities);

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    public function count()
    {
        try {
            if ($this->getRequestMethod() != \Request::HTTP_GET) {
                throw new \BadMethodCallException('Only get method is accepted to access ' . __FUNCTION__);
            }

            \common_Logger::i(print_r($this->getRequestParameters(),true));
            if (!$this->hasRequestParameter(self::TYPE)) {
                throw new \InvalidArgumentException('A valid "' . self::TYPE . '" parameter is required to access ' . __FUNCTION__);
            }

            \common_Logger::i(__METHOD__);
            $type = $this->getRequestParameter(self::TYPE);

            $this->returnJson($this->getSyncService()->count($type));

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

    public function getClassChecksum()
    {
        try {
            // Check if it's post method
            if ($this->getRequestMethod() != \Request::HTTP_GET) {
                throw new \BadMethodCallException('Only get method is accepted to fetch class checksum.');
            }

            $classToSynchronize = false;
            if ($this->hasRequestParameter(self::CLASS_URI)) {
                $class = $this->getClass($this->getRequestParameter(self::CLASS_URI));
                if ($class->isClass()) {
                    $classToSynchronize = $class;
                }
            }

            if (!$classToSynchronize) {
                throw new \InvalidArgumentException('A valid "' . self::CLASS_URI . '" parameter is required to fetch class checksum.');
            }

            $classChecksum = $this->getSyncService()->getLocalClassTree($classToSynchronize->getUri());

            $this->returnJson($classChecksum);

        } catch (\Exception $e) {
            $this->returnFailure($e);
        }
    }

    /**
     * @return SyncService
     */
    protected function getSyncService()
    {
        return $this->getServiceLocator()->get(SyncService::SERVICE_ID);
    }

}