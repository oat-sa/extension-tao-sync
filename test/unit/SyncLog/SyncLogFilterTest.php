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

namespace oat\taoSync\test\unit\SyncLog;

use oat\taoSync\model\SyncLog\SyncLogFilter;
use oat\generis\test\TestCase;

class SyncLogFilterTest extends TestCase
{
    const TEST_FIELD = 'TEST_FIELD';
    const TEST_VALUE = 'TEST_VALUE';

    /**
     * @var SyncLogFilter
     */
    private $object;

    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new SyncLogFilter();
    }

    /**
     * Test addFilter method with invalid operator value.
     */
    public function testAddFilterInvalidOperatorThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->object->addFilter(self::TEST_FIELD, 'INVALID_OPERATOR', self::TEST_VALUE);
    }

    /**
     * Test addFilter method with correct parameters.
     */
    public function testAddFilter()
    {
        $this->object->addFilter(self::TEST_FIELD, SyncLogFilter::OP_EQ, self::TEST_VALUE);
        $this->object->addFilter(self::TEST_FIELD, SyncLogFilter::OP_GTE, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => '=',
                'value' => self::TEST_VALUE,
            ],
            [
                'column' => self::TEST_FIELD,
                'operator' => '>=',
                'value' => self::TEST_VALUE,
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected.');
    }

    /**
     * Test eq method.
     */
    public function testEq()
    {
        $this->object->eq(self::TEST_FIELD, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => '=',
                'value' => self::TEST_VALUE,
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "eq" method.');
    }

    /**
     * Test for neq method.
     */
    public function testNeq()
    {
        $this->object->neq(self::TEST_FIELD, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => '!=',
                'value' => self::TEST_VALUE,
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "neq" method.');
    }

    /**
     * Test for lt method
     */
    public function testLt()
    {
        $this->object->lt(self::TEST_FIELD, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => '<',
                'value' => self::TEST_VALUE,
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "lt" method.');
    }

    /**
     * Test for lte method
     */
    public function testLte()
    {
        $this->object->lte(self::TEST_FIELD, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => '<=',
                'value' => self::TEST_VALUE,
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "lte" method.');
    }

    /**
     * Test for gt method
     */
    public function testGt()
    {
        $this->object->gt(self::TEST_FIELD, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => '>',
                'value' => self::TEST_VALUE,
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "gt" method.');
    }

    /**
     * Test for gte method
     */
    public function testGte()
    {
        $this->object->gte(self::TEST_FIELD, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => '>=',
                'value' => self::TEST_VALUE,
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "gte" method.');
    }

    /**
     * Test for like method
     */
    public function testLike()
    {
        $this->object->like(self::TEST_FIELD, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => 'LIKE',
                'value' => '%' . self::TEST_VALUE . '%',
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "like" method.');
    }

    /**
     * Test for notLike method
     */
    public function testNotLike()
    {
        $this->object->notLike(self::TEST_FIELD, self::TEST_VALUE);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => 'NOT LIKE',
                'value' => '%' . self::TEST_VALUE . '%',
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "notLike" method.');
    }

    /**
     * Test for in method
     */
    public function testIn()
    {
        $this->object->in(self::TEST_FIELD, [self::TEST_VALUE]);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => 'IN',
                'value' => [self::TEST_VALUE],
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "in" method.');
    }

    /**
     * Test for notIn method.
     */
    public function testNotIn()
    {
        $this->object->notIn(self::TEST_FIELD, [self::TEST_VALUE]);

        $expectedFilters = [
            [
                'column' => self::TEST_FIELD,
                'operator' => 'NOT IN',
                'value' => [self::TEST_VALUE],
            ]
        ];
        $filters = $this->object->getFilters();

        $this->assertEquals($expectedFilters, $filters, 'List of filters must be as expected for "notIn" method.');
    }
}
