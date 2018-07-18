<?php

use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeDeliveryLog;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeResult;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeTestSession;

return new \oat\taoSync\model\SynchronizeAllTaskBuilderService([
    \oat\taoSync\model\SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC => [
        SynchronizeData::class,
        SynchronizeResult::class,
        SynchronizeDeliveryLog::class,
        SynchronizeTestSession::class
    ]
]);