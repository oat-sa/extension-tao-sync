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

namespace oat\taoSync\model\Packager;

use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\Exception\PackagerException;
use oat\taoSync\model\Exception\SyncImportException;

class ZipPackager extends ConfigurableService implements PackagerInterface
{
    const MANIFEST_FILENAME = 'manifest.json';

    /** @var string */
    private $directory;

    /**
     * Create folder in a temporary location, create manifest file with synchronization metadata
     *
     * @param $params
     * @return mixed|void
     * @throws PackagerException
     */
    public function initialize($params)
    {
        $this->validateParams($params);
        $this->directory = \tao_helpers_File::createTempDir();

        $this->createManifestFile($params);
    }

    /**
     * Create file with json encoded data, put in the temporary folder
     *
     * @param $type
     * @param $data
     * @return mixed|void
     * @throws PackagerException
     */
    public function store($type, $data)
    {
        if (empty($type) || empty($data)) {
            return;
        }

        $filename = $type . '_' . microtime(true) . '.json';
        $contents = json_encode([$type => $data]);

        $this->createFileInPackage($filename, $contents);
    }

    /**
     * Create a zip archive from the sync files, return archive location
     *
     * @return string
     * @throws \common_Exception
     * @throws \common_exception_Error
     */
    public function finalize()
    {
        $zipPath = \tao_helpers_File::createZip($this->directory);
        \helpers_File::remove($this->directory);

        return $zipPath;
    }

    /**
     * @param File $file
     * @return array
     * @throws SyncImportException
     */
    public function unpack(File $file)
    {
        try {
            $dir = \tao_helpers_File::extractArchive($file);
        } catch (\common_Exception $e) {
            $this->logError('Cannot extract archive with synchronization data. ' . $e->getMessage());
            throw new SyncImportException('Cannot extract archive with synchronization data.');
        }

        $directoryIterator = new \DirectoryIterator($dir);
        $manifest = null;
        $data = [];

        foreach ($directoryIterator as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->getBasename() === self::MANIFEST_FILENAME) {
                $manifest = json_decode(file_get_contents($fileInfo->getRealPath()), true);
            } else {
                $data[] = json_decode(file_get_contents($fileInfo->getRealPath()), true);
            }
        }

        if (!empty($data)) {
            $data = call_user_func_array('array_merge', $data);
        }

        return [
            'manifest' => $manifest,
            'data' => $data
        ];
    }

    private function validateParams($params)
    {
        $requiredParameters = ['organisation_id', 'tao_version', 'box_id'];
        foreach ($requiredParameters as $parameter) {
            if (!isset($params[$parameter])) {
                throw new PackagerException(sprintf('Missing parameter "%s"', $parameter));
            }
        }
    }

    private function createManifestFile($params)
    {
        $manifest = [
            'organisation_id' => $params['organisation_id'],
            'box_id' => $params['box_id'],
            'tao_version' => $params['tao_version'],
        ];

        $contents = json_encode($manifest, JSON_PRETTY_PRINT);

        $this->createFileInPackage(self::MANIFEST_FILENAME, $contents);
    }

    private function createFileInPackage($filename, $contents)
    {
        $filePath = $this->directory . DIRECTORY_SEPARATOR . $filename;
        if (($fileHandle = @fopen($filePath, 'w')) === false) {
            throw new PackagerException(sprintf('Could not create file at "%s"', $filePath));
        }

        try {
            fwrite($fileHandle, $contents);
        } finally {
            @fclose($fileHandle);
        }
    }
}
