<?php

namespace oat\taoSync\model\synchronizer\user\administrator;

use oat\taoSync\model\synchronizer\user\UserSynchronizer;

class RdfAdministratorSynchronizer extends UserSynchronizer implements AdministratorSynchronizer
{
    public function getId()
    {
        return self::SYNC_ADMINISTRATOR;
    }
}