<?php

use oat\taoDelivery\model\execution\DeliveryExecution;
use oat\taoSync\model\Result\SyncResultDataProvider;

return new SyncResultDataProvider(array(
    SyncResultDataProvider::OPTION_STATUS_EXECUTIONS_TO_SYNC => [
        DeliveryExecution::STATE_FINISHED,
    ],
));
