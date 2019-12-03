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
 * Copyright (c) 2019  (original work) Open Assessment Technologies SA;
 *
 * @author Yuri Filippovich
 */

namespace oat\taoSync\model\dataProvider;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\dataHandler\DataHandlerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DataHandlerCollection extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/DataHandlerCollection';

    const OPTION_DATA_HANDLERS = 'taoSync/dataHandlers';

    /**
     * @var DataHandlerInterface[]
     */
    private $dataHandlers = [];

    /**
     * @inheritDoc
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator = null)
    {
        parent::setServiceLocator($serviceLocator);
        $this->initDataHandlers();
    }

    private function initDataHandlers()
    {
        $dataProviders = $this->getOption(self::OPTION_DATA_HANDLERS);

        if (is_array($dataProviders)) {
            $this->dataHandlers = [];

            foreach ($dataProviders as $type => $dataHandler) {
                if ($dataHandler instanceof DataHandlerInterface) {
                    $this->propagate($dataHandler);
                    $this->dataHandlers[$dataHandler->getType()] = $dataHandler;
                }
            }
        }
    }

    /**
     * @param string $type
     * @return DataHandlerInterface|bool
     */
    public function getHandler($type)
    {
        if (array_key_exists($type, $this->dataHandlers)) {
            return $this->dataHandlers[$type];
        }
        return false;
    }
}
