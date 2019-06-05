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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model\User;

use oat\generis\model\user\AuthAdapter;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ServiceManager;

class HandShakeAuthAdapter extends AuthAdapter
{
    use LoggerAwareTrait;

    /**
     * @return \common_user_User|void
     * @throws \Exception
     */
    public function authenticate()
    {
        try {
            $result =  $this->callParentAuthenticate();
            $this->handShakeWithServer();
            return $result;
        } catch (\core_kernel_users_InvalidLoginException $exception) {
            try {
                if ($this->handShakeWithServer()) {
                    return $this->callParentAuthenticate();
                }
                throw new \core_kernel_users_InvalidLoginException(
                    'Fail to login or hand shake has already been done.'
                );

            } catch (\Exception $exception) {
                $this->logError($exception->getMessage());
                throw new \core_kernel_users_InvalidLoginException();
            }
        }
    }

    /**
     * @return \common_user_User
     * @throws \Exception
     */
    protected function callParentAuthenticate()
    {
        return parent::authenticate();
    }

    /**
     * @throws \Exception
     */
    protected function handShakeWithServer()
    {
        /** @var HandShakeClientService $handShakeService */
        $handShakeService = ServiceManager::getServiceManager()->get(HandShakeClientService::SERVICE_ID);

        if (!$handShakeService->isHandShakeAlreadyDone()) {

            $flag = $handShakeService->execute(new HandShakeClientRequest(
                $this->username, $this->password
            ));

            if ($flag){
                $handShakeService->markHandShakeAlreadyDone();
            }

            return $flag;
        }

        return false;
    }

}