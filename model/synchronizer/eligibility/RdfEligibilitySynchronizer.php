<?php

namespace oat\taoSync\model\synchronizer\eligibility;

use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;
use oat\taoTestCenter\model\EligibilityService;

class RdfEligibilitySynchronizer extends AbstractResourceSynchronizer implements EligibilitySynchronizer
{
    public function getId()
    {
        return self::SYNC_ELIGIBILITY;
    }

    protected function getRootClass()
    {
        return $this->getClass(EligibilityService::CLASS_URI);
    }

}