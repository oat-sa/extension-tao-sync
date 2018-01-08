<?php
/**
 * Created by PhpStorm.
 * User: siwane
 * Date: 08/01/18
 * Time: 16:00
 */

namespace oat\taoSync\controller;

use oat\taoSync\model\SyncService;

class SynchronisationApi extends \tao_actions_RestController
{
    const CLASS_URI = 'class-uri';

    public function getClassChecksum()
    {
        return $this->returnJson('test');
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
//                throw new \InvalidArgumentException('A valid "' . self::CLASS_URI . '" parameter is required to fetch class checksum.');
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