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
use PDO;

/**
 * Class ResultSyncHistoryService
 *
 * Storage to store result exported to remote server at synchronisation
 * Mostly used to not resend the result
 *
 * @package oat\taoSync\model\history
 */
class ResultSyncHistoryService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/ResultSyncHistory';

    const OPTION_PERSISTENCE = 'persistence';

    const SYNC_RESULT_TABLE = 'synchronisation_result';

    const SYNC_RESULT_ID = 'id';
    const SYNC_RESULT_STATUS = 'status';
    const SYNC_RESULT_TIME = 'time';
    const SYNC_LOG_SYNCED = 'log_synced';

    const STATUS_SYNCHRONIZED = 'synchronized';
    const STATUS_FAILED = 'failed';

    /**
     * Check if the given result $id is already exported
     *
     * @param $id
     * @return bool
     */
    public function isAlreadyExported($id)
    {
        /** @var QueryBuilder $qbBuilder */
        $qbBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();
        $qb = $qbBuilder
            ->select(self::SYNC_RESULT_ID)
            ->from(self::SYNC_RESULT_TABLE)
            ->where(self::SYNC_RESULT_ID . ' = :id ')
            ->andWhere(self::SYNC_RESULT_STATUS . ' = :status')
            ->setParameter('id', $id)
            ->setParameter('status', self::STATUS_SYNCHRONIZED)
        ;

        /** @var \PDOStatement $statement */
        $statement = $qb->execute();

        try {
            return $statement->rowCount() > 0;
        } catch (\Exception $e) {
            $this->logWarning($e->getMessage());
            return false;
        }
    }

    /**
     * @return array
     */
    public function getResultsWithDeliveryLogNotSynced()
    {
        /** @var QueryBuilder $qbBuilder */
        $qbBuilder = $this->getPersistence()->getPlatform()->getQueryBuilder();

        $qb = $qbBuilder
            ->select(self::SYNC_RESULT_ID)
            ->from(self::SYNC_RESULT_TABLE)
            ->where(self::SYNC_LOG_SYNCED . ' = :log_synced ')
            ->setParameter('log_synced', 0)
        ;

        /** @var \PDOStatement $statement */
        $statement = $qb->execute();

        try {
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            $this->logWarning($e->getMessage());
            return [];
        }
    }

    /**
     * Flags exported results id
     *
     * @param array $entityIds
     * @param string $status
     * @return bool
     */
    public function logResultsAsExported(array $entityIds, $status = self::STATUS_SYNCHRONIZED)
    {
        if (empty($entityIds)) {
            return true;
        }

        $now = $this->getPersistence()->getPlatForm()->getNowExpression();

        $dataToSave = [];
        foreach ($entityIds as $entityId) {
            $dataToSave[] = [
                self::SYNC_RESULT_ID  =>  $entityId,
                self::SYNC_RESULT_STATUS  => $status,
                self::SYNC_RESULT_TIME  => $now,
            ];
        }

        try {
            return $this->getPersistence()->insertMultiple(self::SYNC_RESULT_TABLE, $dataToSave);
        } catch (\Exception $e) {
            $this->logWarning($e->getMessage());
            return false;
        }
    }

    /**
     * Flags exported results id
     *
     * @param array $entityIds
     * @return bool
     */
    public function logResultsLogsAsExported(array $entityIds)
    {
        if (empty($entityIds)) {
            return true;
        }

        $now = $this->getPersistence()->getPlatForm()->getNowExpression();

        $dataToSave = [];
        foreach ($entityIds as $entityId) {
            $dataToSave[] = [
                'conditions' => [
                    self::SYNC_RESULT_ID => $entityId,
                ],
                'updateValues' => [
                    self::SYNC_LOG_SYNCED  => 1,
                    self::SYNC_RESULT_TIME  => $now,
                ],
            ];
        }

        try {
            return $this->getPersistence()->updateMultiple(self::SYNC_RESULT_TABLE, $dataToSave);
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