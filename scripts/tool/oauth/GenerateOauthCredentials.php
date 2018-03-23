<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoSync\scripts\tool\oauth;

use oat\taoOauth\model\user\UserService;
use oat\taoOauth\scripts\tools\GenerateCredentials;
use oat\taoSync\model\SyncService;

class GenerateOauthCredentials extends GenerateCredentials
{
    /**
     * Generate an oauth consumer and add to it taoSync role
     *
     * @return \common_report_Report
     */
    public function run()
    {
        $report = parent::run();

        $this->addSyncRole($this->createdConsumer);

        if ($this->hasOption('command-output')) {
            return \common_report_Report::createInfo(
                'php index.php \'' . ImportOauthCredentials::class . '\'' .
                ' -k ' . $this->key .
                ' -s ' . $this->secret .
                ' -tu ' . $this->tokenUrl .
                ' -u ' . ROOT_URL
            );
        }

        return $report;
    }

    protected function provideOptions()
    {
        return [
            'command-output' => [
                'prefix' => 'cmd',
                'longPrefix' => 'command-output',
                'flag' => true,
                'defaultValue' => 0,
                'description' => 'Print the command to import generated credentials.',
            ],
        ];
    }


    /**
     * Add sync role to consumer user
     *
     * @param \core_kernel_classes_Resource $consumer
     * @return boolean
     */
    protected function addSyncRole(\core_kernel_classes_Resource $consumer)
    {
        $consumerUser =$this->getUserService()->getConsumerUser($consumer);
        try {
            \tao_models_classes_UserService::singleton()->attachRole(
                $consumerUser,
                $this->getResource(SyncService::TAO_SYNC_ROLE)
            );
            return true;
        } catch (\core_kernel_users_Exception $e) {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getCreatedConsumer()
    {
        return $this->createdConsumer;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @return mixed
     */
    public function getTokenUrl()
    {
        return $this->tokenUrl;
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->getServiceLocator()->get(UserService::SERVICE_ID);
    }
}