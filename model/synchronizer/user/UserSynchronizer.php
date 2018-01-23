<?php

namespace oat\taoSync\model\synchronizer\user;

use oat\tao\model\TaoOntology;
use oat\taoSync\model\synchronizer\AbstractResourceSynchronizer;

class UserSynchronizer extends AbstractResourceSynchronizer
{
    const SYNC_USER = 'user';

    public function getId()
    {
        return self::SYNC_USER;
    }

    /**
     * Get the root class of entity to synchronize
     *
     * @return \core_kernel_classes_Class
     */
    protected function getRootClass()
    {
        return $this->getClass(TaoOntology::CLASS_URI_TAO_USER);
    }

    public function deleteMultiple(array $entityIds)
    {
        // Avoid to delete tao user
    }


}