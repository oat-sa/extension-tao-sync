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
use oat\oatbox\log\LoggerService;
use oat\taoSync\model\Export\Exporter\ResultsExporter;
use oat\taoSync\model\Export\ExportService;
use oat\taoSync\model\Packager\PackagerInterface;
use oat\taoSync\model\Packager\ZipPackager;

class ExportServiceTest extends TestCase
{
    /** @var ExportService */
    private $service;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $resultsExporterMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $packagerMock;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    public function setUp()
    {
        parent::setUp();
        $this->resultsExporterMock = $this->createMock(ResultsExporter::class);
        $this->packagerMock = $this->createMock(ZipPackager::class);
        $this->loggerMock = $this->createMock(LoggerService::class);
        $serviceLocator = $this->getServiceLocatorMock([
            PackagerInterface::SERVICE_ID => $this->packagerMock,
            LoggerService::SERVICE_ID => $this->loggerMock,
        ]);
        $this->service = new ExportService([
            ExportService::OPTION_IS_ENABLED => true,
            ExportService::OPTION_EXPORTERS => [
                ResultsExporter::TYPE => $this->resultsExporterMock,
            ]
        ]);
        $this->service->setServiceLocator($serviceLocator);
    }


    /**
     * @expectedException \oat\taoSync\model\Exception\SyncExportException
     */
    public function testExport_WhenExportersNotConfigured_ThenExceptionThrown()
    {
        $this->service->setOptions([
            ExportService::OPTION_IS_ENABLED => true,
        ]);
        $this->service->export([]);
    }

    /**
     * @expectedException \oat\taoSync\model\Exception\SyncExportException
     */
    public function testExport_WhenIncorrectExporterConfigured_ThenExceptionThrown()
    {
        $this->service->setOptions([
            ExportService::OPTION_IS_ENABLED => true,
            ExportService::OPTION_EXPORTERS => [
                ResultsExporter::TYPE => new \stdClass(),
            ]
        ]);
        $this->service->export([]);
    }

    public function testExport_WhenExportIsInitiated_ThenPackageIsCreated()
    {
        $this->packagerMock->expects($this->once())->method('initialize');
        $this->packagerMock->expects($this->once())->method('finalize');
        $this->resultsExporterMock->expects($this->once())->method('export');

        $this->service->export([]);
    }
}