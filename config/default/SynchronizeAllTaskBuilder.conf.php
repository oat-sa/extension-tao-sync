<?php

use oat\taoSync\scripts\tool\synchronisation\SynchronizeData;
use oat\taoSync\scripts\tool\synchronisation\SynchronizeResult;

return new \oat\taoSync\model\SynchronizeAllTaskBuilderService([
    \oat\taoSync\model\SynchronizeAllTaskBuilderService::OPTION_TASKS_TO_RUN_ON_SYNC => [
        SynchronizeData::class,
        SynchronizeResult::class,
    ]
]);