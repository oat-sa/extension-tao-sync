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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\SyncLog\Payload;

use Closure;
use oat\tao\model\datatable\DatatablePayload as DataTablePayloadInterface;
use oat\tao\model\datatable\DatatableRequest;
use oat\taoSync\model\SyncLog\SyncLogFilter;
use oat\taoSync\model\SyncLog\SyncLogServiceInterface;

class DataTablePayload implements DataTablePayloadInterface
{
    /**
     * @var SyncLogFilter;
     */
    private $syncLogFilter;

    /**
     * @var SyncLogServiceInterface
     */
    private $syncLogService;

    /**
     * @var DatatableRequest
     */
    private $request;

    /**
     * @var Closure
     */
    private $rowCustomizer;

    /**
     * DataTablePayload constructor.
     *
     * @param SyncLogFilter             $filter
     * @param DatatableRequest          $request
     * @param SyncLogServiceInterface   $syncLogService
     */
    public function __construct(SyncLogFilter $filter, DatatableRequest $request, SyncLogServiceInterface $syncLogService)
    {
        $this->syncLogFilter = $filter;
        $this->request = $request;
        $this->syncLogService = $syncLogService;
    }

    /**
     * Set filters.
     *
     * @param SyncLogFilter $filters
     */
    public function setFilters (SyncLogFilter $filters)
    {
        $this->syncLogFilter = $filters;
    }

    /**
     * Set function to customise result row.
     *
     * You can control list of returned fields, change the value of a field or add extra field(s);
     *
     * @param Closure $func
     * @return DataTablePayload
     */
    public function customiseRowBy(Closure $func)
    {
        $this->rowCustomizer = $func;

        return $this;
    }

    /**
     * Get DataTable payload.
     *
     * @return array
     */
    public function getPayload()
    {
        $this->applyRequestFilters();

        $countTotal = $this->count();
        $page = $this->request->getPage();
        $limit = $this->request->getRows();

        $this->syncLogFilter->setLimit($limit)
            ->setOffset($limit * ($page - 1));

        $sortBy = $this->request->getSortBy();
        if ($sortBy !== null) {
            $this->syncLogFilter->setSortBy($sortBy)
                ->setSortOrder($this->request->getSortOrder());
        }

        $syncLogRecords = $this->syncLogService->search($this->syncLogFilter);
        $syncLogRecords = $this->customizeData($syncLogRecords);

        $data = [
            'rows'    => $limit,
            'page'    => $page,
            'amount'  => count($syncLogRecords),
            'total'   => ceil($countTotal / $limit),
            'data'    => $syncLogRecords,
        ];

        return $data;
    }

    /**
     * Get customized data if the customizer function is set
     *
     * @param array $collection
     * @return array
     */
    private function customizeData(array $collection)
    {
        if (!is_null($this->rowCustomizer)) {
            foreach ($collection as $key => $row) {
                $collection[$key] = call_user_func($this->rowCustomizer, $row);
            }
        }

        return $collection;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->syncLogService->count($this->syncLogFilter);
    }

    /**
     * Apply filters from request.
     */
    private function applyRequestFilters()
    {
        $filters = $this->request->getFilters();

        foreach ($filters as $fieldName => $filterValue) {
            if (empty($filterValue)) {
                continue;
            }

            if (is_array($filterValue)) {
                $this->syncLogFilter->in($fieldName, $filterValue);
                continue;
            }

            $this->syncLogFilter->eq($fieldName, (string) $filterValue);
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getPayload();
    }
}
