<?php

namespace oat\taoSync\model\synchronizer\user\proctor;

use oat\taoSync\model\synchronizer\user\UserSynchronizer;

class RdfProctorSynchronizer extends UserSynchronizer implements ProctorSynchronizer
{
    public function getId()
    {
        return self::SYNC_PROCTOR;
    }
}