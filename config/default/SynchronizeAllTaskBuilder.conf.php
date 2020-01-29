<?php

use oat\taoSync\model\SynchronizeAllTaskBuilderService;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeDeliveryLog;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeResult;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeTestSession;
use oat\taoSync\scripts\tool\ConnectionStats\ConnectionSpeedChecker;

return new SynchronizeAllTaskBuilderService([
    SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC => [
        SynchronizeData::class,
        SynchronizeResult::class,
        SynchronizeDeliveryLog::class,
        SynchronizeTestSession::class,
        ConnectionSpeedChecker::class,
    ]
]);
