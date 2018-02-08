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

    const SYNC_RESULT_TABLE = 'synchronisationResult';

    const SYNC_RESULT_ID = 'id';
    const SYNC_RESULT_STATUS = 'status';
    const SYNC_RESULT_TIME = 'time';

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
        $query = 'SELECT ' . self::SYNC_RESULT_ID .
            ' FROM ' . self::SYNC_RESULT_TABLE .
            ' WHERE ' . self::SYNC_RESULT_ID . ' = ?' .
            ' AND ' . self::SYNC_RESULT_STATUS . ' = ?';

        /** @var \PDOStatement $statement */
        $statement = $this->getPersistence()->query($query, array(
            $id,
            self::STATUS_SYNCHRONIZED,
        ));

        try {
            return $statement->rowCount() > 0;
        } catch (\Exception $e) {
            \common_Logger::w($e->getMessage());
            return false;
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
            \common_Logger::w($e->getMessage());
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