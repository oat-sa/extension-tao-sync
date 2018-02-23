<?php

use oat\taoSync\model\ResultService;

return new ResultService(array(
    ResultService::OPTION_CHUNK_SIZE => ResultService::DEFAULT_CHUNK_SIZE,
    ResultService::OPTION_DELETE_AFTER_SEND => false
));
