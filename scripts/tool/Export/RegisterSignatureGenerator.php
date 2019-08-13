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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\scripts\tool\Export;


use common_report_Report;
use oat\oatbox\extension\InstallAction;
use oat\taoSync\model\Packager\SignatureGenerator;
use oat\taoSync\model\Packager\SignatureGeneratorInterface;

/**
 * Registers manifest signature generator and configures salt value
 *
 * sudo -u www-data php index.php '\oat\taoSync\scripts\tool\RegisterSignatureGenerator'
 */
class RegisterSignatureGenerator extends InstallAction
{
    /**
     * @param $params
     * @return common_report_Report
     * @throws \common_Exception
     */
    public function __invoke($params)
    {
        foreach ($params as $param) {
            $parts = explode('=', $param);
            if (count($parts) < 2) {
                continue;
            }

            $option = array_shift($parts);

            if ($option === '--salt') {
                $salt = implode('=', $parts);
                break;
            }
        }

        if (!isset($salt)) {
            throw new \Exception('Please specify the --salt=...');
        }

        $signatureGenerator = new SignatureGenerator([
            SignatureGenerator::OPTION_SALT => $salt,
        ]);

        $this->registerService(SignatureGeneratorInterface::SERVICE_ID, $signatureGenerator);

        return \common_report_Report::createSuccess('SignatureGenerator service is registered');
    }
}
