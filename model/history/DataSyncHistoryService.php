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

namespace oat\taoSync\model\history;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class SyncHistoryService
 *
 * Storage to store action applied on entity at synchronisation
 * Mostly used to find not updated entities to delete
 *
 * @package oat\taoSync\model\history
 */
class DataSyncHistoryService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/syncHistoryService';

    const OPTION_PERSISTENCE = 'persistence';

    const SYNC_TABLE = 'synchronisation';
    const SYNC_ID = 'id';
    const SYNC_NUMBER = 'sync_id';
    const SYNC_ENTITY_ID = 'entity_id';
    const SYNC_ENTITY_TYPE = 'type';
    const SYNC_ACTION = 'action';
    const SYNC_TIME = 'time';

    const SYNCHRO_URI = 'http://www.taotesting.com/ontologies/synchro.rdf#synchro';
    const SYNCHRO_ID = 'http://www.taotesting.com/ontologies/synchro.rdf#identifier';
    const SYNCHRO_TASK = 'http://www.taotesting.com/ontologies/synchro.rdf#task';

    const ACTION_TOUCHED = 'touched';
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';

    protected $synchroId;

    /**
     * Flag created entities in synchronisation history with current synchro version
     *
     * @param $type
     * @param array $entityIds
     * @return bool
     * @throws \core_kernel_persistence_Exception
     */
    public function logCreatedEntities($type, array $entityIds)
    {
        if (empty($entityIds)) {
            return true;
        }
        return $this->insert($type, self::ACTION_CREATED, $entityIds);
    }

    /**
     * Flag not changed entities in synchronisation history with current synchro version
     *
     * @param $type
     * @param array $entityIds
     * @return bool
     * @throws \core_kernel_persistence_Exception
     */
    public function logNotChangedEntities($type, array $entityIds)
    {
        if (empty($entityIds)) {
            return true;
        }
        return $this->update($type, self::ACTION_TOUCHED, $entityIds);
    }

    /**
     * Flag updated entities in synchronisation history with current synchro version
     *
     * @param $type
     * @param array $entityIds
     * @return bool
     * @throws \core_kernel_persistence_Exception
     */
    public function logUpdatedEntities($type, array $entityIds)
    {
        if (empty($entityIds)) {
            return true;
        }
        return $this->update($type, self::ACTION_UPDATED, $entityIds);
    }

    /**
     * Flag deleted entities in synchronisation history with current synchro version
     *
     * @param $type
     * @param array $entityIds
     * @return bool
     * @throws \core_kernel_persistence_Exception
     */
    public function logDeletedEntities($type, array $entityIds)
    {
        if (empty($entityIds)) {
            return true;
        }
        return $this->update($type, self::ACTION_DELETED, $entityIds);
    }

    /**
     * Get the entities not updated by the current synchro.
     * It means that there are not in remote server anymore
     *
     * @param $type
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    public function getNotUpdatedEntityIds($type)
    {
        /** @var QueryBuilder $qbBuilder */
        $qbBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();
        $qb = $qbBuilder
            ->select(self::SYNC_ENTITY_ID)
            ->from(self::SYNC_TABLE)
            ->where(self::SYNC_NUMBER . ' <> :sync_number ')
            ->andWhere(self::SYNC_ENTITY_TYPE . ' = :type')
            ->andWhere(self::SYNC_ACTION . ' <> :action')
            ->setParameter('sync_number', $this->getCurrentSynchroId())
            ->setParameter('type', $type)
            ->setParameter('action', self::ACTION_DELETED)
        ;

        /** @var \PDOStatement $statement */
        $results = $qb->execute()->fetchAll(\PDO::FETCH_ASSOC);

        $returnValue = [];
        foreach ($results as $result) {
            $returnValue[] = $result[self::SYNC_ENTITY_ID];
        }

        return $returnValue;
    }

    /**
     * Create a new synchronisation process by incrementing the synchronisation version
     *
     * @param array $params
     * @return int
     * @throws \core_kernel_persistence_Exception
     */
    public function createSynchronisation(array $params = [])
    {
        $lastId = $this->getCurrentSynchroId();
        $this->synchroId = $lastId + 1;
        $this->getResource(self::SYNCHRO_URI)->setPropertyValue($this->getProperty(self::SYNCHRO_ID), $this->synchroId);
        return $this->synchroId;
    }

    /**
     * Get the current synchro identifier store in ontology
     *
     * @return int
     * @throws \core_kernel_persistence_Exception
     */
    public function getCurrentSynchroId()
    {
        if (!$this->synchroId) {
            $synchro = $this->getResource(self::SYNCHRO_URI);
            $synchroIdProperty = $synchro->getOnePropertyValue($this->getProperty(self::SYNCHRO_ID));
            if (is_null($synchroIdProperty)) {
                $this->synchroId = 0;
            } else {
                $this->synchroId = (int) $synchroIdProperty->literal;
            }
        }
        return $this->synchroId;
    }

    /**
     * Insert multiple entity for the given type. Each record will have the $action
     *
     * @param $type
     * @param $action
     * @param array $entityIds
     * @return bool
     * @throws \core_kernel_persistence_Exception
     */
    protected function insert($type, $action, array $entityIds)
    {
        $syncId = $this->getCurrentSynchroId();
        $now = $this->getPersistence()->getPlatForm()->getNowExpression();

        $dataToSave = [];
        foreach ($entityIds as $entityId) {
            $dataToSave[] = [
                self::SYNC_NUMBER  =>  $syncId,
                self::SYNC_ENTITY_ID  => $entityId,
                self::SYNC_ENTITY_TYPE  => $type,
                self::SYNC_ACTION  => $action,
                self::SYNC_TIME => $now,
            ];
        }

        try {
            return $this->getPersistence()->insertMultiple(self::SYNC_TABLE, $dataToSave);
        } catch (\Exception $e) {
            $this->logWarning($e->getMessage());
            return false;
        }
    }

    /**
     * Update multiple entity for the given type. Each record will have the $action
     *
     * @param $type
     * @param $action
     * @param array $entityIds
     * @return bool
     * @throws \core_kernel_persistence_Exception
     */
    protected function update($type, $action, $entityIds)
    {
        $syncId = $this->getCurrentSynchroId();
        $now = $this->getPersistence()->getPlatForm()->getNowExpression();

        $dataToSave = [];
        foreach ($entityIds as $entityId) {
            $dataToSave[] = [
                'conditions' => [
                    self::SYNC_ENTITY_ID => $entityId,
                    self::SYNC_ENTITY_TYPE => $type,
                ],
                'updateValues' => [
                    self::SYNC_ACTION => $action,
                    self::SYNC_NUMBER => $syncId,
                    self::SYNC_TIME => $now,
                ]
            ];
        }

        try {
            return $this->getPersistence()->updateMultiple(self::SYNC_TABLE, $dataToSave);
        } catch (\Exception $e) {
            $this->logWarning($e->getMessage());
            return false;
        }
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    public function getPersistence()
    {
        $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);
        return $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);
    }
}
