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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\SyncLog;

use DateTime;
use oat\oatbox\extension\script\MissingOptionException;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\report\SynchronizationReport;
use oat\taoSync\model\SyncLogStorageInterface;

class SyncLogService extends ConfigurableService implements SyncLogServiceInterface
{
    const OPTION_STORAGE = 'storage';

    /**
     * @var SyncLogStorageInterface
     */
    private $storage;

    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if (!$this->hasOption(self::OPTION_STORAGE)) {
            throw new MissingOptionException('Storage implementation is required option', self::OPTION_STORAGE);
        }
    }

    /**
     * @return SyncLogStorageInterface
     */
    private function getStorage() {
        if (empty($this->storage)) {
            $this->storage = $this->getServiceLocator()->get($this->getOption(self::OPTION_STORAGE));
        }

        return $this->storage;
    }

    /**
     * Create new record in storage implementation.
     *
     * @param SyncLogEntity $entity
     * @return mixed|void
     */
    public function create(SyncLogEntity $entity)
    {
        return $this->getStorage()->create($entity);
    }

    /**
     * Update existing synchronization log record.
     *
     * @param SyncLogEntity $entity
     * @return mixed|void
     */
    public function update(SyncLogEntity $entity)
    {
        return $this->getStorage()->update($entity);
    }

    /**
     * @param $id
     * @return SyncLogEntity
     * @throws \common_exception_Error
     */
    public function getById($id)
    {
        $syncData = $this->getStorage()->getById($id);

        return $this->createEntityFromArray($syncData);
    }

    /**
     * @param $syncId
     * @param $clientId
     * @return SyncLogEntity
     * @throws \common_exception_Error
     */
    public function getBySyncIdAndClientId($syncId, $clientId)
    {
        $syncData = $this->getStorage()->getBySyncIdAndClientId($syncId, $clientId);

        return $this->createEntityFromArray($syncData);
    }

    /**
     * @param $data
     * @return SyncLogEntity
     * @throws \common_exception_Error
     */
    private function createEntityFromArray($data)
    {
        $syncLogEntity = new SyncLogEntity(
            $data['sync_id'],
            $data['client_id'],
            $data['organization_id'],
            $data['data'],
            $data['status'],
            SynchronizationReport::jsonUnserialize($data['data']),
            DateTime::createFromFormat(SyncLogEntity::DATE_TIME_FORMAT, $data['created_at']),
            $data['id']
        );
        return $syncLogEntity;
    }
}
