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

namespace oat\taoSync\model\history\byOrganisationId;

use Doctrine\DBAL\Query\QueryBuilder;
use oat\taoSync\model\history\DataSyncHistoryService;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;

/**
 * Class DataSyncHistoryByOrgIdService
 *
 * Storage to store action applied on entity at synchronisation
 * Mostly used to find not updated entities to delete
 *
 * @package oat\taoSync\model\history\byOrganisationId
 */
class DataSyncHistoryByOrgIdService extends DataSyncHistoryService
{
    use OrganisationIdTrait;

    const SYNC_ORG_ID = 'organisationId';

    protected $organisationId;

    /**
     * Get the entities not updated by the current synchro (scoped to organisation id).
     * It means that there are not in remote server anymore
     *
     * Check is scoped to organisation id
     *
     * @param $type
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    public function getNotUpdatedEntityIds($type)
    {
        $orgId = $this->getCurrentOrganisationId();
        $queryBuilder1 = $this->getPersistence()->getPlatform()->getQueryBuilder();
        $exprBuilder = $queryBuilder1->expr();
        $query1 = $queryBuilder1->select('count(id)')
            ->from(self::SYNC_TABLE, 'b')
            ->where($exprBuilder->eq('a.' . self::SYNC_ENTITY_ID, 'b.' . self::SYNC_ENTITY_ID))
            ->andWhere($exprBuilder->neq('b.' . self::SYNC_ORG_ID, ':orgId'))
            ->andWhere($exprBuilder->neq('b.' . self::SYNC_ACTION, ':action'))
            ->setParameter('orgId',  $orgId)
            ->setParameter('action', self::ACTION_DELETED)
        ;

        $queryBuilder2 = $this->getPersistence()->getPlatform()->getQueryBuilder();
        $exprBuilder = $queryBuilder2->expr();
        $query2 = $queryBuilder2
            ->select('a.' . self::SYNC_ENTITY_ID)
            ->addSelect('(' . $query1->getSQL() . ') as exists_with_other_org_id')
            ->from(self::SYNC_TABLE, 'a')
            ->where($exprBuilder->neq('a.' . self::SYNC_NUMBER, ':sync_number'))
            ->andWhere($exprBuilder->eq('a.' . self::SYNC_ENTITY_TYPE, ':type'))
            ->andWhere($exprBuilder->neq('a.' . self::SYNC_ACTION, ':action'))
            ->andWhere($exprBuilder->eq('a.' . self::SYNC_ORG_ID, ':orgId'))
            ->setParameter('sync_number',  $this->getCurrentSynchroId())
            ->setParameter('type', $type)
            ->setParameter('action', self::ACTION_DELETED)
            ->setParameter('orgId', $orgId)
        ;

        $results = $query2->execute()->fetchAll(\PDO::FETCH_ASSOC);
        $entitiesToDelete = $fakeDeletedEntities = [];
        foreach ($results as $result) {
            if ($result['exists_with_other_org_id'] !== 0) {
                $fakeDeletedEntities[] = $result[self::SYNC_ENTITY_ID];
            } else {
                $entitiesToDelete[] = $result[self::SYNC_ENTITY_ID];
            }
        }
        if (!empty($fakeDeletedEntities)) {
            $this->logDeletedEntities($type, $fakeDeletedEntities);
        }
        return $entitiesToDelete;
    }

    /**
     * Create a new synchronisation process by incrementing the synchronisation version
     *
     * Save the organisationId extracted from $params as synchronisation property
     *
     * @param array $params
     * @return int
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     * @throws \core_kernel_persistence_Exception
     */
    public function createSynchronisation(array $params = [])
    {
        $lastId = $this->getCurrentSynchroId();
        $this->synchroId = $lastId + 1;
        $this->getResource(self::SYNCHRO_URI)->setPropertiesValues(array(
            self::SYNCHRO_ID => $this->synchroId,
            TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY => $this->getOrganisationIdFromOption($params)
        ));

        return $this->synchroId;
    }

    /**
     * Get the current organisation id from the synchronisation resource
     *
     * @return int
     * @throws \core_kernel_persistence_Exception
     */
    protected function getCurrentOrganisationId()
    {
        if (!$this->organisationId) {
            $orgId = $this->getResource(self::SYNCHRO_URI)
                ->getOnePropertyValue($this->getProperty(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY));
            if (is_null($orgId)) {
                $this->organisationId = 0;
            } else {
                $this->organisationId = (int) $orgId->literal;
            }
        }
        return $this->organisationId;
    }

    /**
     * Insert multiple entity for the given type. Each record will have the $action
     *
     * Add organisation id property
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
                self::SYNC_ORG_ID => $this->getCurrentOrganisationId(),
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
     * Check if the database contains the entity id with a different org id, in this case insert
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

        $orgId = $this->getCurrentOrganisationId();

        $toInsert = [];
        foreach ($entityIds as $k => $id) {
            $qbBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();
            $qb = $qbBuilder
                ->select(self::SYNC_ENTITY_ID)
                ->from(self::SYNC_TABLE)
                ->where(self::SYNC_ENTITY_ID . ' = :id')
                ->andWhere(self::SYNC_ORG_ID . ' = :orgId')
                ->setParameter('id',  $id)
                ->setParameter('orgId', $orgId)
            ;

            if ($qb->execute()->rowCount() == 0) {
                $toInsert[] = $id;
                unset($entityIds[$k]);
            }
        }

        if (!empty($toInsert)) {
            $this->insert($type, $action, $toInsert);
        }

        $dataToSave = [];
        foreach ($entityIds as $entityId) {
            $dataToSave[] = [
                'conditions' => [
                    self::SYNC_ENTITY_ID => $entityId,
                    self::SYNC_ENTITY_TYPE => $type,
                    self::SYNC_ORG_ID => $orgId,
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
}
