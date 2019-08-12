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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */
namespace oat\taoSync\model\Export\Exporter;

use oat\oatbox\service\ConfigurableService;
use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoSync\model\Result\SyncResultDataFormatter;
use oat\taoSync\model\Result\SyncResultDataProvider;

class ResultsExporter extends ConfigurableService implements EntityExporterInterface
{
    const SERVICE_ID = 'taoSync/ResultsExporter';

    const TYPE = 'results';

    const OPTION_BATCH_SIZE = 'batchSize';
    const DEFAULT_BATCH_SIZE = 10;

    /**
     * @param $packager
     * @return void
     * @throws \common_exception_Error
     * @throws \common_exception_NoImplementation
     * @throws \common_exception_NotFound
     * @throws \core_kernel_persistence_Exception
     */
    public function export($packager)
    {
        $results = [];
        $dataProvider = $this->getDataProviderService();
        foreach ($dataProvider->getDeliveryExecutions($this->getBatchSize()) as $deliveryExecutionBatch) {
            if (empty($deliveryExecutionBatch)) {
                continue;
            }
            /** @var DeliveryExecution $deliveryExecution */
            foreach ($deliveryExecutionBatch as $deliveryExecution) {
                $deliveryExecutionId = $deliveryExecution->getIdentifier();
                $formattedDeliveryExecution = $this->getSyncResultsFormatterService()->format($deliveryExecution);
                if (empty($formattedDeliveryExecution)) {
                    continue;
                }
                $results[$deliveryExecutionId] = $formattedDeliveryExecution;
            }

            $packager->store(self::TYPE, $results);
        }
    }

    /**
     * @return SyncResultDataProvider
     */
    private function getDataProviderService()
    {
        return $this->getServiceLocator()->get(SyncResultDataProvider::SERVICE_ID);
    }

    /**
     * @return SyncResultDataFormatter
     */
    private function getSyncResultsFormatterService()
    {
        return $this->getServiceLocator()->get(SyncResultDataFormatter::SERVICE_ID);
    }

    private function getBatchSize()
    {
        return $this->hasOption(self::OPTION_BATCH_SIZE) ? $this->getOption(self::OPTION_BATCH_SIZE) : self::DEFAULT_BATCH_SIZE;
    }
}