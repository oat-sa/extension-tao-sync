<?php

use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoSync\model\ResultService;

return new ResultService(array(
    ResultService::OPTION_CHUNK_SIZE => ResultService::DEFAULT_CHUNK_SIZE,
    ResultService::OPTION_DELETE_AFTER_SEND => false,
    ResultService::OPTION_STATUS_EXECUTIONS_TO_SYNC => [
        DeliveryExecution::STATE_FINISHIED,
    ],
));
