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

 namespace oat\taoSync\model\SyncLog\Storage;

 use Doctrine\DBAL\Query\QueryBuilder;
 use oat\oatbox\service\exception\InvalidServiceManagerException;
 use oat\oatbox\extension\script\MissingOptionException;
 use oat\oatbox\service\ConfigurableService;
 use oat\taoSync\model\SyncLog\SyncLogEntity;
 use oat\taoSync\model\SyncLog\SyncLogFilter;
 use common_exception_NotFound;
 use common_persistence_SqlPersistence;
 use Doctrine\DBAL\Connection;

 class RdsSyncLogStorage extends ConfigurableService implements SyncLogStorageInterface
 {
     const SERVICE_ID = 'taoSync/RdsSyncLogStorage';

     const OPTION_PERSISTENCE_ID = 'persistenceId';

     const TABLE_NAME = 'synchronisation_log';

     /**
      * @var string
      */
     private $persistenceId = null;

     /**
      * @var common_persistence_SqlPersistence
      */
     private $persistence = null;

     /**
      * SyncLogService constructor.
      * @param array $options
      * @throws MissingOptionException
      */
     public function __construct(array $options = array())
     {
         parent::__construct($options);

         if (!$this->hasOption(self::OPTION_PERSISTENCE_ID)) {
             throw new MissingOptionException('Persistence ID is required option.', self::OPTION_PERSISTENCE_ID);
         }
         $this->persistenceId = $this->getOption(self::OPTION_PERSISTENCE_ID);
     }

     /**
      * @return common_persistence_SqlPersistence
      */
     public function getPersistence()
     {
         if ($this->persistence === null) {
             $this->persistence = $this->getServiceLocator()
                 ->get(\common_persistence_Manager::SERVICE_ID)
                 ->getPersistenceById($this->persistenceId);
         }

         return $this->persistence;
     }

     /**
      * @return \Doctrine\DBAL\Query\QueryBuilder
      * @throws \oat\oatbox\service\exception\InvalidServiceManagerException
      */
     private function getQueryBuilder()
     {
         return $this->getPersistence()->getPlatForm()->getQueryBuilder()->from(self::TABLE_NAME);
     }

     /**
      * Store SyncLogEntity in rds storage.
      *
      * @param SyncLogEntity $entity
      * @return mixed|void
      */
     public function create(SyncLogEntity $entity)
     {
         $this->getPersistence()->insert(
             self::TABLE_NAME,
             [
                 SyncLogStorageInterface::COLUMN_SYNC_ID => $entity->getSyncId(),
                 SyncLogStorageInterface::COLUMN_BOX_ID => $entity->getBoxId(),
                 SyncLogStorageInterface::COLUMN_ORGANIZATION_ID => $entity->getOrganizationId(),
                 SyncLogStorageInterface::COLUMN_DATA => json_encode($entity->getData()),
                 SyncLogStorageInterface::COLUMN_STATUS => $entity->getStatus(),
                 SyncLogStorageInterface::COLUMN_REPORT => json_encode($entity->getReport()),
                 SyncLogStorageInterface::COLUMN_STARTED_AT => $entity->getStartTime()->format(SyncLogEntity::DATE_TIME_FORMAT)
             ]
         );
     }

     /**
      * Update synchronization log record.
      *
      * @param SyncLogEntity $entity
      * @return mixed
      */
     public function update(SyncLogEntity $entity)
     {
         $qb = $this->getQueryBuilder();
         $qb->update(self::TABLE_NAME)
             ->set(SyncLogStorageInterface::COLUMN_STATUS, $qb->createNamedParameter($entity->getStatus()))
             ->set(SyncLogStorageInterface::COLUMN_DATA, $qb->createNamedParameter(json_encode($entity->getData())))
             ->set(SyncLogStorageInterface::COLUMN_REPORT, $qb->createNamedParameter(json_encode($entity->getReport())));

         $finishTime = $entity->getFinishTime();
         if ($finishTime instanceof \DateTime) {
             $finishedAt = $entity->getFinishTime()->format(SyncLogEntity::DATE_TIME_FORMAT);
             $qb->set(SyncLogStorageInterface::COLUMN_FINISHED_AT, $qb->createNamedParameter($finishedAt));
         }

         $qb->where(SyncLogStorageInterface::COLUMN_ID . ' = ' . $qb->createNamedParameter($entity->getId()));

         return $this->getPersistence()->exec($qb->getSQL(), $qb->getParameters());
     }

     /**
      * @param $id
      * @return SyncLogEntity
      * @throws common_exception_NotFound
      * @throws InvalidServiceManagerException
      */
     public function getById($id)
     {
         $queryBuilder = $this->getQueryBuilder();
         $queryBuilder->select('*')
             ->where(SyncLogStorageInterface::COLUMN_ID . ' = ' . $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT));

         $sql = $queryBuilder->getSQL();
         $params = $queryBuilder->getParameters();
         /** @var \PDOStatement $stmt */
         $stmt = $this->getPersistence()->query($sql, $params);
         $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

         if (count($data) != 1) {
             throw new common_exception_NotFound('There is no synchronization log record for provided ID.');
         }

         return $data[0];
     }

     /**
      * @param $syncId
      * @param $boxId
      * @return SyncLogEntity
      * @throws common_exception_NotFound
      * @throws InvalidServiceManagerException
      */
     public function getBySyncIdAndBoxId($syncId, $boxId)
     {
         $queryBuilder = $this->getQueryBuilder();
         $queryBuilder->select('*')
             ->from(self::TABLE_NAME)
             ->where(SyncLogStorageInterface::COLUMN_SYNC_ID . ' = ' . $queryBuilder->createNamedParameter($syncId, \PDO::PARAM_INT))
             ->andWhere(SyncLogStorageInterface::COLUMN_BOX_ID . ' = ' . $queryBuilder->createNamedParameter($boxId, \PDO::PARAM_STR));

         $sql = $queryBuilder->getSQL();
         $params = $queryBuilder->getParameters();
         /** @var \PDOStatement $stmt */
         $stmt = $this->getPersistence()->query($sql, $params);
         $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

         if (count($data) != 1) {
             throw new common_exception_NotFound('There is no unique synchronization log record.');
         }

         return $data[0];
     }

     /**
      * Get total amount of synchronization logs by provided filters.
      *
      * @param SyncLogFilter $filter
      * @return integer
      */
     public function count(SyncLogFilter $filter)
     {
         try {
             $qb = $this->getQueryBuilder()
                 ->select('COUNT(*)')
                 ->from(self::TABLE_NAME);

             $this->applyFilters($qb, $filter);

             return (int) $qb->execute()->fetchColumn();
         } catch (\Exception $e) {
             $this->logError('Counting synchronization logs failed: '. $e->getMessage());
         }

         return 0;
     }


     /**
      * @param SyncLogFilter $filter
      * @return array
      */
     public function search(SyncLogFilter $filter)
     {
         try {
             $qb = $this->getQueryBuilder()
                 ->select($filter->getColumns())
                 ->from(self::TABLE_NAME);

             $qb->setMaxResults($filter->getLimit());
             $qb->setFirstResult($filter->getOffset());

             if ($filter->getSortBy()) {
                 $qb->orderBy($filter->getSortBy(), $filter->getSortOrder());
             }
             $this->applyFilters($qb, $filter);

             return $qb->execute()->fetchAll();
         } catch (\Exception $e) {
             $this->logError('Error searching for synchronization logs: ' . $e->getMessage());

             return [];
         }
     }

     /**
      * @param QueryBuilder $qb
      * @param SyncLogFilter $syncLogFilter
      */
     private function applyFilters(QueryBuilder $qb, SyncLogFilter $syncLogFilter)
     {
         foreach ($syncLogFilter->getFilters() as $filter) {
             if (is_array($filter['value'])) {
                 $qb->andWhere($qb->expr()->in($filter['column'], $qb->createNamedParameter($filter['value'], Connection::PARAM_STR_ARRAY)));
             } else {
                 $qb->andWhere("{$filter['column']} {$filter['operator']} " . $qb->createNamedParameter($filter['value']));
             }
         }
     }
 }
