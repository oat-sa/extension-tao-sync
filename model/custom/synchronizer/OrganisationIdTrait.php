<?php
/**
 * Created by PhpStorm.
 * User: siwane
 * Date: 18/01/18
 * Time: 12:35
 */

namespace oat\taoSync\model\custom\synchronizer;


trait OrganisationIdTrait
{
    protected function getOrganisationIdFromOption(array $options = [])
    {
        if (!isset($options['orgId'])) {
            throw new \common_exception_NotFound('Organisation id cannot be retrieved from parameters');
        }
        return $options['orgId'];
    }
}