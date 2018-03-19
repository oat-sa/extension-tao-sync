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
use oat\oatbox\service\ServiceManager;

class HandShakeAuthAdapter extends AuthAdapter
{
    /**
     * @return \common_user_User|void
     * @throws \Exception
     */
    public function authenticate()
    {
       try {
           return parent::authenticate();
       } catch (\core_kernel_users_InvalidLoginException $exception){
           if ($this->handShakeWithServer()){
               return parent::authenticate();
           }
       }
    }

    /**
     * @throws \Exception
     */
    protected function handShakeWithServer()
    {
        /** @var HandShakeService $handShakeService */
        $handShakeService = ServiceManager::getServiceManager()->get(HandShakeService::SERVICE_ID);
        return $handShakeService->execute(new HandShakeRequest(
            $this->username, $this->password
        ));
    }

}