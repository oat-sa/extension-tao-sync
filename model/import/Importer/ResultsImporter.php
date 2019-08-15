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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model\import\Importer;

use oat\oatbox\event\EventManager;
use oat\taoSync\model\event\SyncRequestEvent;
use oat\taoSync\model\ResultService;
use common_report_Report as Report;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class ResultsImporter
 * @package oat\taoSync\model\import\Importer
 * @author Aleh Hutnikau, <huntikau@1pt.com>
 */
class ResultsImporter implements EntityImporterInterface, ServiceLocatorAwareInterface
{

    use ServiceLocatorAwareTrait;

    const TYPE = 'results';

    /**
     * @param array $data
     * @param array $manifest
     * @return mixed|void
     */
    public function import(array $data, array $manifest)
    {
        $report = Report::createInfo('Import results');
        $this->getEventManager()->trigger(new SyncRequestEvent($manifest, $report));
        return $this->getSyncResultService()->importDeliveryResults($data, $manifest);
    }

    /**
     * @return EventManager
     */
    private function getEventManager()
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }

    /**
     * @return array|ResultService|object
     */
    protected function getSyncResultService()
    {
        return $this->getServiceLocator()->get(ResultService::SERVICE_ID);
    }
}