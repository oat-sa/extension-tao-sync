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
    private $syncLogService;
    private $request;

    /**
     * @var \Closure
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

    public function setFilters (SyncLogFilter $filters)
    {
        $this->syncLogFilter = $filters;
    }

    /**
     * You can pass an anonymous function to customise the final payload: either to change the value of a field or to add extra field(s);
     *
     * The function will be bind to the task log entity (TaskLogEntity) so $this can be used inside of the closure.
     * The return value needs to be an array.
     *
     * For example:
     * <code>
     *  $payload->customiseRowBy(function (){
     *      $row['extraField'] = 'value';
     *      $row['extraField2'] = $this->getParameters()['some_parameter_key'];
     *      $row['createdAt'] = \tao_helpers_Date::displayeDate($this->getCreatedAt());
     *
     *      return $row;
     *  });
     * </code>
     *
     * @param \Closure $func
     * @return DataTablePayload
     */
    public function customiseRowBy($func)
    {
        $this->rowCustomizer = $func;

        return $this;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        $this->applyRequestFilters();

        $countTotal = $this->count();
        $page = $this->request->getPage();
        $limit = $this->request->getRows();

        $this->syncLogFilter->setLimit($limit)
            ->setOffset($limit * ($page - 1))
            ->setSortBy($this->request->getSortBy())
            ->setSortOrder($this->request->getSortOrder());

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
//                $newCustomiser = $this->rowCustomizer->bindTo($row, $row);
//                $customizedRecord = (array) $newCustomiser();

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
