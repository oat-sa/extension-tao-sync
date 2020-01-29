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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoSync\model\SyncLog;

use InvalidArgumentException;
use oat\taoSync\model\SyncLog\Storage\SyncLogStorageInterface;

/**
 * Class SyncLogFilter
 * @package oat\taoSync\model\SyncLog
 */
class SyncLogFilter
{
    const OP_EQ  = '=';
    const OP_NEQ = '!=';
    const OP_LT  = '<';
    const OP_LTE = '<=';
    const OP_GT  = '>';
    const OP_GTE = '>=';
    const OP_LIKE = 'LIKE';
    const OP_NOT_LIKE = 'NOT LIKE';
    const OP_IN = 'IN';
    const OP_NOT_IN = 'NOT IN';

    private $filters = [];
    private $limit;
    private $offset;
    private $sortBy;
    private $sortOrder;
    private $columns = [
        SyncLogStorageInterface::COLUMN_ID,
        SyncLogStorageInterface::COLUMN_BOX_ID,
        SyncLogStorageInterface::COLUMN_SYNC_ID,
        SyncLogStorageInterface::COLUMN_ORGANIZATION_ID,
        SyncLogStorageInterface::COLUMN_DATA,
        SyncLogStorageInterface::COLUMN_STATUS,
        SyncLogStorageInterface::COLUMN_REPORT,
        SyncLogStorageInterface::COLUMN_CLIENT_STATE,
        SyncLogStorageInterface::COLUMN_STARTED_AT,
        SyncLogStorageInterface::COLUMN_FINISHED_AT,
    ];

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return mixed
     */
    public function getSortBy()
    {
        return $this->sortBy;
    }

    /**
     * @param string $sortBy
     * @return SyncLogFilter
     */
    public function setSortBy($sortBy)
    {
        if (!is_string($sortBy)) {
            throw new InvalidArgumentException('Invalid column name provided.');
        }
        $this->sortBy = $sortBy;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param string $sortOrder
     * @return SyncLogFilter
     */
    public function setSortOrder($sortOrder)
    {
        if (!is_string($sortOrder)) {
            throw new InvalidArgumentException('Invalid value for sort order provided.');
        }
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return SyncLogFilter
     */
    public function setLimit($limit)
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return SyncLogFilter
     */
    public function setOffset($offset)
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function addFilter($field, $operator, $value)
    {
        if (!is_string($field)) {
            throw new InvalidArgumentException('Invalid field name provided.');
        }

        $this->assertValidOperator($operator);
        $this->filters[] =  [
            'column' => (string) $field,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function eq($field, $value)
    {
        return $this->addFilter($field, self::OP_EQ, $value);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function neq($field, $value)
    {
        return $this->addFilter($field, self::OP_NEQ, $value);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function lt($field, $value)
    {
        return $this->addFilter($field, self::OP_LT, $value);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function lte($field, $value)
    {
        return $this->addFilter($field, self::OP_LTE, $value);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function gt($field, $value)
    {
        return $this->addFilter($field, self::OP_GT, $value);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function gte($field, $value)
    {
        return $this->addFilter($field, self::OP_GTE, $value);
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function like($field, $value)
    {
        return $this->addFilter($field, self::OP_LIKE, "%{$value}%");
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return SyncLogFilter
     */
    public function notLike($field, $value)
    {
        return $this->addFilter($field, self::OP_NOT_LIKE, "%{$value}%");
    }

    /**
     * @param string $field
     * @param array $value
     * @return SyncLogFilter
     */
    public function in($field, array $value)
    {
        return $this->addFilter($field, self::OP_IN, $value);
    }

    /**
     * @param string $field
     * @param array $value
     * @return SyncLogFilter
     */
    public function notIn($field, array $value)
    {
        return $this->addFilter($field, self::OP_NOT_IN, $value);
    }

    /**
     * @param $op
     * @throws InvalidArgumentException
     */
    private function assertValidOperator($op)
    {
        $operators = [
            self::OP_EQ,
            self::OP_NEQ,
            self::OP_LT,
            self::OP_LTE,
            self::OP_GT,
            self::OP_GTE,
            self::OP_LIKE,
            self::OP_NOT_LIKE,
            self::OP_IN,
            self::OP_NOT_IN,
        ];
        if (!in_array($op, $operators)) {
            throw new InvalidArgumentException('Operator "' . $op . '"" is not a valid operator.');
        }
    }
}
