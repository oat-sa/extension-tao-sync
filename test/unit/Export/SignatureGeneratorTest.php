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

namespace oat\taoSync\test\unit\Export;

use oat\generis\test\TestCase;
use oat\taoSync\model\Packager\SignatureGenerator;
use oat\taoSync\model\Packager\SignatureGeneratorInterface;

class SignatureGeneratorTest extends TestCase
{
    /** @var SignatureGeneratorInterface */
    private $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new SignatureGenerator([
            SignatureGenerator::OPTION_SALT => 'test',
        ]);
    }

    public function testGenerate_WhenProvidedDataTheSame_ThenGeneratedSignatureTheSame()
    {
        $this->assertEquals($this->service->generate(['test']), $this->service->generate(['test']));
        $this->assertNotEquals($this->service->generate(['fizz']), $this->service->generate(['buzz']));
    }

    public function testGenerate_WhenSaltDifferent_ThenGeneratedSignatureDifferent()
    {
        $result1 = $this->service->generate(['test']);
        $this->service->setOption(SignatureGenerator::OPTION_SALT, 'fizzbuzz');
        $result2 = $this->service->generate(['test']);
        $this->assertNotEquals($result1, $result2);
    }
}