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
use oat\taoSync\model\Exception\SyncDataProviderException;

class SyncDataProviderCollection extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/SyncDataProviderCollection';

    const OPTION_DATA_PROVIDERS = 'taoSync/dataProviders';

    /**
     * @var AbstractDataProvider[]
     */
    private $dataProviders;

    /**
     * @param array $params
     * @return array
     * @throws SyncDataProviderException
     */
    public function getData(array $params)
    {
        $this->initDataProviders();
       return $this->getProvidersData($this->dataProviders, $params);
    }

    /**
     * @param AbstractDataProvider[] $dataProviders
     * @param array $params
     * @return array
     * @throws SyncDataProviderException
     */
    private function getProvidersData(array $dataProviders, array $params)
    {
        $result = [];

        foreach ($dataProviders as $type => $provider) {
            if (array_key_exists($type, $params)) {
                throw new SyncDataProviderException('Invalid data provider configuration: recursion detected');
            }
            $result[$type] = $provider->getData($params);
            if ($childProviders = $provider->getChildProviders()) {
                $childParams = array_merge($params, [$type => $result[$type]]);
                $result = array_merge($result, $this->getProvidersData($childProviders, $childParams));
            }
        }
        return $result;
    }

    /**
     * @param string $type
     * @return AbstractDataProvider|bool
     * @throws SyncDataProviderException
     */
    public function getProvider($type)
    {
        $this->initDataProviders();
        if (!array_key_exists($type, $this->dataProviders)) {
            throw new SyncDataProviderException(sprintf('data provider %s not found', $type));
        }
        return $this->dataProviders[$type];
    }

    private function initDataProviders()
    {
        if (is_null($this->dataProviders)) {
            $this->dataProviders = [];

            $dataProviders = $this->getOption(self::OPTION_DATA_PROVIDERS);

            if (is_array($dataProviders)) {
                foreach ($dataProviders as $type => $dataProvider) {
                    if ($dataProvider instanceof AbstractDataProvider) {
                        $this->propagate($dataProvider);
                        $this->dataProviders[$dataProvider->getType()] = $dataProvider;
                    }
                }
            }
        }
    }
}
