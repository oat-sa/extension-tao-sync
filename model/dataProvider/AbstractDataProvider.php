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
use oat\taoSync\model\Exception\DataProviderException;
use oat\taoSync\export\dataProvider\dataFormatter\AbstractDataFormatter;

abstract class AbstractDataProvider extends ConfigurableService
{
    const OPTION_FORMATTER = 'formatter';

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
     * @return string|bool
     */
    public function getParent()
    {
        return false;
    }

    /**
     * @return AbstractDataFormatter|bool
     * @throws DataProviderException
     */
    protected function getDataFormatter()
    {
        if ($this->hasOption(self::OPTION_FORMATTER)) {
            $formatter = $this->getOption(self::OPTION_FORMATTER);

            if (!$formatter instanceof AbstractDataFormatter) {
                throw new DataProviderException('Invalid data formatter for ' . __CLASS__);
            }

            return $this->propagate($formatter);
        }
        return false;
    }

    /**
     * @param array $params
     * @return array
     * @throws DataProviderException
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
