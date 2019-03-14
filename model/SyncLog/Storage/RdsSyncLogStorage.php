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

 use DateTime;
 use common_report_Report as Report;
 use Doctrine\DBAL\Query\QueryBuilder;
 use oat\oatbox\service\exception\InvalidServiceManagerException;
 use oat\oatbox\extension\script\MissingOptionException;
 use oat\oatbox\service\ConfigurableService;
 use oat\taoSync\model\SyncLog\SyncLogEntity;
 use oat\taoSync\model\SyncLog\SyncLogFilter;
 use common_exception_NotFound;
 use InvalidArgumentException;
 use common_exception_Error;
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
     public function __construct(array $options)
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
      * @return QueryBuilder
      */
     private function getQueryBuilder()
     {
         return $this->getPersistence()->getPlatForm()->getQueryBuilder()->from(self::TABLE_NAME);
     }

     /**
      * Store SyncLogEntity in rds storage.
      *
      * @param SyncLogEntity $entity
      * @return integer Id of created record.
      */
     public function create(SyncLogEntity $entity)
     {

         $qb = $this->getQueryBuilder();
         $qb->insert(self::TABLE_NAME)
             ->values([
                 SyncLogStorageInterface::COLUMN_SYNC_ID            => $qb->createNamedParameter($entity->getSyncId()),
                 SyncLogStorageInterface::COLUMN_BOX_ID             => $qb->createNamedParameter($entity->getBoxId()),
                 SyncLogStorageInterface::COLUMN_ORGANIZATION_ID    => $qb->createNamedParameter($entity->getOrganizationId()),
                 SyncLogStorageInterface::COLUMN_DATA               => $qb->createNamedParameter(json_encode($entity->getData())),
                 SyncLogStorageInterface::COLUMN_STATUS             => $qb->createNamedParameter($entity->getStatus()),
                 SyncLogStorageInterface::COLUMN_REPORT             => $qb->createNamedParameter(json_encode($entity->getReport())),
                 SyncLogStorageInterface::COLUMN_CLIENT_STATE       => $qb->createNamedParameter(json_encode($entity->getClientState())),
                 SyncLogStorageInterface::COLUMN_STARTED_AT         => $qb->createNamedParameter($entity->getStartTime()->format(SyncLogEntity::DATE_TIME_FORMAT))
             ])
             ->execute();

         return (int) $qb->getConnection()->lastInsertId();
     }

     /**
      * Update synchronization log record.
      *
      * @param SyncLogEntity $entity
      * @return integer Number of updated records.
      */
     public function update(SyncLogEntity $entity)
     {
         if (!$entity->getId()) {
             throw new InvalidArgumentException('Provided entity must have unique identifier.');
         }

         $qb = $this->getQueryBuilder();
         $qb->update(self::TABLE_NAME)
             ->set(SyncLogStorageInterface::COLUMN_STATUS, $qb->createNamedParameter($entity->getStatus()))
             ->set(SyncLogStorageInterface::COLUMN_DATA, $qb->createNamedParameter(json_encode($entity->getData())))
             ->set(SyncLogStorageInterface::COLUMN_REPORT, $qb->createNamedParameter(json_encode($entity->getReport())))
             ->set(SyncLogStorageInterface::COLUMN_CLIENT_STATE, $qb->createNamedParameter(json_encode($entity->getClientState())));

         $finishTime = $entity->getFinishTime();
         if ($finishTime instanceof \DateTime) {
             $finishedAt = $entity->getFinishTime()->format(SyncLogEntity::DATE_TIME_FORMAT);
             $qb->set(SyncLogStorageInterface::COLUMN_FINISHED_AT, $qb->createNamedParameter($finishedAt));
         }

         $qb->where(SyncLogStorageInterface::COLUMN_ID . ' = ' . $qb->createNamedParameter($entity->getId()));

         return $qb->execute();
     }

     /**
      * Get synchronization log record by id.
      *
      * @param integer $id
      * @return SyncLogEntity
      *
      * @throws common_exception_Error
      * @throws common_exception_NotFound
      */
     public function getById($id)
     {
         if (!is_int($id)) {
             throw new InvalidArgumentException('Provided ID parameter must be an integer.');
         }
         $queryBuilder = $this->getQueryBuilder();
         $queryBuilder->select([
                self::COLUMN_ID,
                self::COLUMN_BOX_ID,
                self::COLUMN_SYNC_ID,
                self::COLUMN_ORGANIZATION_ID,
                self::COLUMN_DATA,
                self::COLUMN_STATUS,
                self::COLUMN_REPORT,
                self::COLUMN_CLIENT_STATE,
                self::COLUMN_STARTED_AT,
                self::COLUMN_FINISHED_AT,
             ])
             ->where(SyncLogStorageInterface::COLUMN_ID . ' = ' . $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT));

         $data = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);

         if (count($data) !== 1) {
             throw new common_exception_NotFound('There is no synchronization log record for provided ID.');
         }

         $data[0][self::COLUMN_ID] = (int) $data[0][self::COLUMN_ID];
         $data[0][self::COLUMN_SYNC_ID] = (int) $data[0][self::COLUMN_SYNC_ID];

         return $this->createEntityFromArray($data[0]);
     }

     /**
      * Get synchronization log record by synchronization ID and client ID.
      *
      * @param integer $syncId
      * @param string $boxId
      * @return SyncLogEntity
      *
      * @throws common_exception_Error
      * @throws common_exception_NotFound
      */
     public function getBySyncIdAndBoxId($syncId, $boxId)
     {
         $queryBuilder = $this->getQueryBuilder();
         $queryBuilder->select('*')
             ->from(self::TABLE_NAME)
             ->where(SyncLogStorageInterface::COLUMN_SYNC_ID . ' = ' . $queryBuilder->createNamedParameter($syncId, \PDO::PARAM_INT))
             ->andWhere(SyncLogStorageInterface::COLUMN_BOX_ID . ' = ' . $queryBuilder->createNamedParameter($boxId, \PDO::PARAM_STR));

         $data = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);
         if (count($data) !== 1) {
             throw new common_exception_NotFound('There is no unique synchronization log record.');
         }

         $data[0][self::COLUMN_ID] = (int) $data[0][self::COLUMN_ID];
         $data[0][self::COLUMN_SYNC_ID] = (int) $data[0][self::COLUMN_SYNC_ID];

         return $this->createEntityFromArray($data[0]);
     }

     /**
      * Get total amount of synchronization logs by provided filters.
      *
      * @param SyncLogFilter $filter
      * @return integer
      */
     public function count(SyncLogFilter $filter)
     {
         $qb = $this->getQueryBuilder()
             ->select('COUNT(*)')
             ->from(self::TABLE_NAME);
         $this->applyFilters($qb, $filter);

         return (int) $qb->execute()->fetchColumn();
     }


     /**
      * @param SyncLogFilter $filter
      * @return array
      */
     public function search(SyncLogFilter $filter)
     {
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
     }

     /**
      * @param QueryBuilder $qb
      * @param SyncLogFilter $syncLogFilter
      */
     private function applyFilters(QueryBuilder $qb, SyncLogFilter $syncLogFilter)
     {
         foreach ($syncLogFilter->getFilters() as $filter) {
             if (is_array($filter['value'])) {
                 if ($filter['operator'] === SyncLogFilter::OP_NOT_IN) {
                     $qb->andWhere($qb->expr()->notIn($filter['column'], $qb->createNamedParameter($filter['value'], Connection::PARAM_STR_ARRAY)));
                 } else {
                     $qb->andWhere($qb->expr()->in($filter['column'], $qb->createNamedParameter($filter['value'], Connection::PARAM_STR_ARRAY)));
                 }
             } else if ($filter['operator'] === SyncLogFilter::OP_LIKE) {
                 $qb->andWhere($qb->expr()->like($filter['column'], $qb->createNamedParameter($filter['value'])));
             } else if ($filter['operator'] === SyncLogFilter::OP_NOT_LIKE) {
                 $qb->andWhere($qb->expr()->notLike($filter['column'], $qb->createNamedParameter($filter['value'])));
             } else {
                 $qb->andWhere("{$filter['column']} {$filter['operator']} " . $qb->createNamedParameter($filter['value']));
             }
         }
     }

     /**
      * @param array $data
      * @return SyncLogEntity
      * @throws common_exception_Error
      */
     private function createEntityFromArray(array $data)
     {
         if (!is_array($data[self::COLUMN_DATA])) {
             $data[self::COLUMN_DATA] = json_decode($data[self::COLUMN_DATA], true);
         }

         if (!$data[self::COLUMN_REPORT] instanceof Report) {
             $data[self::COLUMN_REPORT] =  Report::jsonUnserialize($data[self::COLUMN_REPORT]);
         }

         if (!is_array($data[self::COLUMN_CLIENT_STATE])) {
             $data[self::COLUMN_CLIENT_STATE] = json_decode($data[self::COLUMN_CLIENT_STATE], true);
         }

         if (!$data[self::COLUMN_STARTED_AT] instanceof DateTime) {
             $data[self::COLUMN_STARTED_AT] = new DateTime((string) $data[self::COLUMN_STARTED_AT]);
         }

         if (!$data[self::COLUMN_FINISHED_AT] instanceof DateTime) {
             $data[self::COLUMN_FINISHED_AT] = new DateTime((string) $data[self::COLUMN_FINISHED_AT]);
         }

         $syncLogEntity = new SyncLogEntity(
             $data[self::COLUMN_SYNC_ID],
             $data[self::COLUMN_BOX_ID],
             $data[self::COLUMN_ORGANIZATION_ID],
             $data[self::COLUMN_DATA],
             $data[self::COLUMN_STATUS],
             $data[self::COLUMN_REPORT],
             $data[self::COLUMN_STARTED_AT],
             $data[self::COLUMN_FINISHED_AT],
             $data[self::COLUMN_ID]
         );
         $syncLogEntity->setClientState($data[self::COLUMN_CLIENT_STATE]);

         return $syncLogEntity;
     }
 }
