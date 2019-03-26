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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\SyncLog;

use common_exception_NotFound;
use common_exception_Error;
use oat\oatbox\extension\script\MissingOptionException;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\Exception\SyncLogEntityNotFound;
use oat\taoSync\model\SyncLog\Storage\SyncLogStorageInterface;

/**
 * Class SyncLogService
 * Service to work with persistence implementations for synchronization logs.
 *
 * @package oat\taoSync\model\SyncLog
 */
class SyncLogService extends ConfigurableService implements SyncLogServiceInterface
{
    const OPTION_STORAGE = 'storage';

    /**
     * @var SyncLogStorageInterface
     */
    private $storage;

    /**
     * SyncLogService constructor.
     * @param array $options
     * @throws MissingOptionException
     */
    public function __construct(array $options)
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
     * @return SyncLogEntity
     */
    public function create(SyncLogEntity $entity)
    {
        $entityId = $this->getStorage()->create($entity);
        $this->setEntityId($entity, $entityId);

        return $entity;
    }

    /**
     * Update existing synchronization log record.
     *
     * @param SyncLogEntity $entity
     * @return integer Number of updated records.
     */
    public function update(SyncLogEntity $entity)
    {
        return $this->getStorage()->update($entity);
    }

    /**
     * Get SyncLogEntity by ID.
     *
     * @param integer $id
     * @return SyncLogEntity
     *
     * @throws SyncLogEntityNotFound
     * @throws common_exception_Error
     */
    public function getById($id)
    {
        try {
            return $this->getStorage()->getById($id);
        } catch (common_exception_NotFound $e) {
            throw new SyncLogEntityNotFound($e->getMessage());
        }
    }

    /**
     * Get SyncLogEntity by synchronization ID and box ID.
     *
     * @param integer $syncId
     * @param string $boxId
     * @return SyncLogEntity
     *
     * @throws SyncLogEntityNotFound
     * @throws common_exception_Error
     */
    public function getBySyncIdAndBoxId($syncId, $boxId)
    {
        try {
            return $this->getStorage()->getBySyncIdAndBoxId($syncId, $boxId);
        } catch (common_exception_NotFound $e) {
            throw new SyncLogEntityNotFound($e->getMessage());
        }

    }

    /**
     * Get total amount of synchronization logs by provided filters.
     *
     * @param SyncLogFilter $filter
     * @return integer
     */
    public function count(SyncLogFilter $filter)
    {
        return $this->getStorage()->count($filter);
    }

    /**
     * Search synchronization records by provided filters.
     *
     * @param SyncLogFilter $filter
     * @return array
     */
    public function search(SyncLogFilter $filter)
    {
        return $this->getStorage()->search($filter);
    }

    /**
     * @param SyncLogEntity $entity
     * @param $entityId
     * @throws \ReflectionException
     */
    private function setEntityId(SyncLogEntity $entity, $entityId)
    {
        $syncLogEntityReflection = new \ReflectionClass($entity);
        $property = $syncLogEntityReflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $entityId);
    }
}
