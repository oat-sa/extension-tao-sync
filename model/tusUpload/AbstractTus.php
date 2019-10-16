<?php

namespace oat\taoSync\model\tusUpload;

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\tusUpload\exception\TusException;

abstract class AbstractTus extends ConfigurableService
{


    const OPTION_FILE_STORAGE = 'abstract';

    protected $tusFileStorageService;

    /**
     * Getting path to the folder with Generated packages for synchronization
     * @return LocalFileStorageService
     */
    protected function getTusFileStorageService()
    {
        if (static::OPTION_FILE_STORAGE == 'abstract') {
            throw new TusException('File storage not configured');
        }
        if (!$this->tusFileStorageService) {
            $this->tusFileStorageService = $this->propagate($this->getOption(static::OPTION_FILE_STORAGE));
        }
        return $this->tusFileStorageService;
    }
}
