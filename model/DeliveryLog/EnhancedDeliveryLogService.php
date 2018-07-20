<?php

namespace oat\taoSync\model\DeliveryLog;

use Doctrine\DBAL\Query\QueryBuilder;
use oat\oatbox\service\ConfigurableService;
use oat\taoProctoring\model\deliveryLog\DeliveryLog;
use oat\taoProctoring\model\deliveryLog\implementation\RdsDeliveryLogService;

class EnhancedDeliveryLogService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/EnhancedDeliveryLog';

    const OPTION_PERSISTENCE = 'persistence';

    const COLUMN_IS_SYNCED = 'is_synced';

    const LOG_IS_AFTER_SESSION_SYNCED = 'log_is_after_session_sync';

    /**
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function markAllLogsSynced()
    {
        /** @var QueryBuilder $qbBuilder */
        $qbBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();

        $qbBuilder
            ->update(RdsDeliveryLogService::TABLE_NAME, 'dl')
            ->set('dl.'.static::COLUMN_IS_SYNCED, ':value')
            ->setParameter('value', 1)
        ;

        return $qbBuilder->execute();
    }

    /**
     * @param $resultId
     * @return bool
     */
    public function hasAllDeliveryLogsSynced($resultId)
    {
        /** @var QueryBuilder $qbBuilder */
        $qbBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();

        $qb = $qbBuilder
            ->select(RdsDeliveryLogService::ID)
            ->from(RdsDeliveryLogService::TABLE_NAME)
            ->where(RdsDeliveryLogService::DELIVERY_EXECUTION_ID . ' = :delivery_execution_id ')
            ->andWhere(static::COLUMN_IS_SYNCED . ' = :is_synced ')
            ->setParameter('delivery_execution_id', $resultId)
            ->setParameter('is_synced', 0)
        ;
        /** @var \PDOStatement $statement */
        $statement = $qb->execute();

        try {
            return $statement->rowCount() === 0;
        } catch (\Exception $e) {
            $this->logWarning($e->getMessage());
            return false;
        }
    }
    /**
     * @param array $ids
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function markLogsAsSynced(array $ids)
    {
        /** @var QueryBuilder $qbBuilder */
        $qbBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();

        $qbBuilder
            ->update(RdsDeliveryLogService::TABLE_NAME, 'dl')
            ->set('dl.'.static::COLUMN_IS_SYNCED, ':value')
            ->where(RdsDeliveryLogService::ID. ' IN (:ids)')
            ->setParameter('ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)
            ->setParameter('value', 1)
        ;

        return $qbBuilder->execute();
    }

    /**
     * @param bool $shouldDecode
     * @return mixed
     */
    public function getLogsToSynced($shouldDecode = true)
    {
        return $this->getDeliveryLog()->search([
            static::COLUMN_IS_SYNCED => '0'
        ], [], $shouldDecode);
    }

    /**
     * @param array $logsToBeInserted
     * @return mixed
     */
    public function insertMultiple(array $logsToBeInserted)
    {
        $cleanedLogs = [];
        foreach ($logsToBeInserted as $log) {
            unset($log[static::LOG_IS_AFTER_SESSION_SYNCED]);
            $cleanedLogs[] = $log;
        }
        return $this->getDeliveryLog()->insertMultiple($cleanedLogs);
    }

    /**
     * @return array|DeliveryLog
     */
    protected function getDeliveryLog()
    {
        return $this->getServiceLocator()->get(DeliveryLog::SERVICE_ID);
    }

    /**
     * @return \common_persistence_SqlPersistence
     */
    protected function getPersistence()
    {
        $persistenceId = $this->getOption(self::OPTION_PERSISTENCE);
        return $this->getServiceLocator()->get(\common_persistence_Manager::SERVICE_ID)->getPersistenceById($persistenceId);
    }
}