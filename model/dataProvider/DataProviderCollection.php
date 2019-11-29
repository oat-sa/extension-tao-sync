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
 * @author Oleksandr Zagovorychev <zagovorichev@1pt.com>
 */

namespace oat\taoSync\model\dataProvider;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\Exception\DataProviderException;
use Zend\ServiceManager\ServiceLocatorInterface;

class DataProviderCollection extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/DataProviderCollection';

    const OPTION_DATA_PROVIDERS = 'taoSync/dataProviders';

    private $dataProviders;

    private $rootDataProviders = [];
    private $childDataProviders = [];

    /**
     * @inheritDoc
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator = null)
    {
        parent::setServiceLocator($serviceLocator);
        $this->initDataProviders();
    }

    /**
     * @param array $params
     * @return array
     * @throws DataProviderException
     */
    public function getData($params)
    {
        return $this->getProvidersData($this->rootDataProviders, $params);
    }

    /**
     * @param array $types
     * @param array $params
     * @return array
     * @throws DataProviderException
     */
    private function getProvidersData(array $types, array $params)
    {
        $result = [];

        foreach ($types as $type) {
            $dataProvider = $this->getProvider($type);
            $result[$type] = $dataProvider->getData($params);
            if (array_key_exists($type, $this->childDataProviders)) {
                $childParams = array_merge($params, [$type => $result[$type]]);
                $result = array_merge($result, $this->getProvidersData($this->childDataProviders[$type], $childParams));
            }
        }
        return $result;
    }

    /**
     * @param string $type
     * @return AbstractDataProvider|bool
     * @throws DataProviderException
     */
    public function getProvider($type)
    {
        if (!array_key_exists($type, $this->dataProviders)) {
            throw new DataProviderException(sprintf('data provider %s not found', $type));
        }
        return $this->dataProviders[$type];
    }

    /**
     * @throws DataProviderException
     */
    private function initDataProviders()
    {
        if (is_null($this->dataProviders)) {
            $dataProviders = $this->getOption(self::OPTION_DATA_PROVIDERS);

            if (!is_array($dataProviders)) {
                throw new DataProviderException('Data providers not set');
            }
            $this->dataProviders = [];

            foreach ($dataProviders as $type => $dataProvider) {
                if ($dataProvider instanceof AbstractDataProvider) {
                    $this->propagate($dataProvider);
                    $this->dataProviders[$dataProvider->getType()] = $dataProvider;
                }
            }
            $this->buildDataProvidersTree();
        }
    }

    /**
     * @throws DataProviderException
     */
    private function buildDataProvidersTree()
    {
        foreach ($this->dataProviders as $type => $dataProvider) {
            $parent = $dataProvider->getParent();
            if (!$parent) {
                $this->rootDataProviders[] = $type;
            } else {
                if (!array_key_exists($parent, $this->dataProviders)) {
                    throw new DataProviderException(sprintf('Invalid parent provider %s', $parent));
                }
                if (!array_key_exists($parent, $this->childDataProviders)) {
                    $this->childDataProviders[$parent] = [];
                }
                $this->childDataProviders[$parent][] = $type;
            }
        }
        if (!count($this->rootDataProviders)) {
            throw new DataProviderException(sprintf('Invalid data providers config: no root data providers'));
        }

        $processed = $this->checkProcessed($this->rootDataProviders, []);

        if(count($processed) !== count($this->dataProviders)) {
            throw new DataProviderException(sprintf('Invalid data providers config: recursion detected'));
        }
    }

    /**
     * @param array $types
     * @param array $processed
     * @return array
     * @throws DataProviderException
     */
    private function checkProcessed(array $types, array $processed)
    {
        foreach ($types as $type) {
            if (in_array($type, $processed)) {
                throw new DataProviderException(sprintf('Invalid data providers config: recursion detected'));
            }
            $processed[] = $type;
            if (array_key_exists($type, $this->childDataProviders)) {
                $processed = $this->checkProcessed($this->childDataProviders[$type], $processed);
            }
        }
        return $processed;
    }
}
