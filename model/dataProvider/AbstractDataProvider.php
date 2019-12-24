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
 */

namespace oat\taoSync\model\dataProvider;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\Exception\SyncDataProviderException;
use oat\taoSync\model\dataProvider\dataFormatter\AbstractDataFormatter;

abstract class AbstractDataProvider extends ConfigurableService
{
    const OPTION_FORMATTER = 'formatter';

    const OPTION_CHILD_PROVIDERS = 'childProviders';

    /**
     * @var AbstractDataFormatter
     */
    private $formatter;

    /**
     * @param array $options
     * @throws SyncDataProviderException
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        if ($this->hasOption(self::OPTION_FORMATTER)) {
            $formatter = $this->getOption(self::OPTION_FORMATTER);

            if (!$formatter instanceof AbstractDataFormatter) {
                throw new SyncDataProviderException('Invalid data formatter for ' . __CLASS__);
            }
        }
    }

    /**
     * Returns required data
     *
     * @param array $params
     * @return array
     */
    abstract public function getResources(array $params);

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return AbstractDataProvider[]
     */
    public function getChildProviders()
    {
        $childProviders = [];

        if (is_array($this->getOption(self::OPTION_CHILD_PROVIDERS))) {
            $providers = $this->getOption(self::OPTION_CHILD_PROVIDERS);
            foreach ($providers as $provider) {
                if ($provider instanceof AbstractDataProvider) {
                    $this->propagate($provider);
                    $childProviders[$provider->getType()] = $provider;
                }
            }
        }

        return $childProviders;
    }

    /**
     * @return AbstractDataFormatter|bool
     */
    protected function getDataFormatter()
    {
        if ($this->formatter) {
            return $this->formatter;
        }

        if ($this->hasOption(self::OPTION_FORMATTER)) {
            $this->formatter = $this->propagate($this->getOption(self::OPTION_FORMATTER));
            return $this->formatter;
        }
        return false;
    }

    /**
     * @param array $params
     * @return array
     */
    public function getData($params)
    {
        $data = $this->getResources($params);

        if ($this->getDataFormatter()) {
            $data = $this->getDataFormatter()->formatAll($data);
        }
        return $data;
    }
}
