<?php

use oat\taoSync\model\DeliveryLog\SyncDeliveryLogService;

return new SyncDeliveryLogService(array(
    SyncDeliveryLogService::OPTION_CHUNK_SIZE => SyncDeliveryLogService::DEFAULT_CHUNK_SIZE,
    SyncDeliveryLogService::OPTION_SHOULD_DECODE_BEFORE_SYNC => true
));
