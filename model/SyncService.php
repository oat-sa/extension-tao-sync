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

namespace oat\taoSync\model;

use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\event\EventManager;
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\controller\SynchronisationApi;
use oat\taoSync\model\client\SynchronisationClient;
use oat\taoSync\model\event\SynchronisationStart;
use oat\taoSync\model\history\DataSyncHistoryService;
use common_report_Report as Report;
use oat\taoSync\model\synchronizer\RdfClassSynchronizer;
use oat\taoSync\model\synchronizer\Synchronizer;
use oat\taoSync\model\SyncLog\SyncLogDataHelper;
use Psr\Log\LogLevel;

/**
 * Class SyncService
 *
 * Class called to synchronize data. It constructs the synchronizers and them to manage the process
 *
 * @package oat\taoSync\model
 */
class SyncService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/syncService';
    const TAO_SYNC_ROLE = 'http://www.tao.lu/Ontologies/generis.rdf#taoSyncManager';
    const PROPERTY_CONSUMER_USER = 'http://www.tao.lu/Ontologies/taoSync.rdf#ConsumerUser';
    const PROPERTY_ASSIGNED_SYNC_USER = 'http://www.taotesting.com/Ontologies/TAOTestCenter.rdf#assignedSyncUser';

    const OPTION_SYNCHRONIZERS = 'synchronizers';
    const OPTION_CHECK_ACTIVE_SESSIONS = 'checkActiveSessions';
    const OPTION_CHUNK_SIZE = 'chunkSize';

    const DEFAULT_CHUNK_SIZE = 100;

    /** @var Synchronizer[]  */
    protected $synchronizers = [];

    /** @var \common_report_Report */
    protected $report;

    /** @var array Synchronization parameters */
    protected $params;

    /**
     * Starting point to synchronization
     *
     * If $type is not present, all configured synchronizers will be used to sync data
     * If $type is present, it must be set in ocnfig under 'synchronizers' key
     * Optional $params will be passed to process
     *
     * @param null $type
     * @param array $params
     * @return Report
     * @throws \common_Exception
     * @throws \common_exception_BadRequest
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    public function synchronize($type = null, array $params = [])
    {
        $syncId = $this->getSyncId($params);
        $this->report = Report::createInfo('Starting synchronization n° "' . $syncId . '" ...');

        $this->getEventManager()->trigger(
            new SynchronisationStart($this->getResource(DataSyncHistoryService::SYNCHRO_URI))
        );

        try {
            if (is_null($type)) {
                foreach ($this->getAllTypes() as $type) {
                    $this->synchronizeType($type, $params);
                }
            } else {
                $this->synchronizeType($type, $params);
            }
        } catch (\Exception $e) {
            $this->report->add(Report::createFailure('An error has occurred : ' . $e->getMessage()));
        }

        return $this->report;
    }

    /**
     * Fetch the data related to the given synchronizer $type
     *
     * Add query option to chunk the data set
     * Add an additional 'nextCallUrl' parameter as callBack for next chunk (see $this->synchronizeType())
     * nextCallUrl is added only if there are chunk+1 records. The '+1' will be the start of next call
     *
     * @param $type
     * @param $params
     * @return array
     * @throws \common_exception_BadRequest
     */
    public function fetch($type, $params)
    {
        $response = [
            SynchronisationApi::PARAM_TYPE => $type
        ];

        $options = $params;
        $options['order'] = [
            Entity::CREATED_AT => 'asc',
            OntologyRdfs::RDFS_LABEL => 'asc',
        ];

        $limit = $this->getChunkSize() + 1;
        $options['limit'] = $limit;

        if (isset($options['nextResource'])) {
            $startEpoch = $this->getSynchronizer($type)->getEntityProperty($options['nextResource'], Entity::CREATED_AT);
            if (!is_null($startEpoch)) {
                $options['startCreatedAt'] = $startEpoch;
            }
        }

        $entities = $this->getSynchronizer($type)->fetch($options);

        if (count($entities) == $limit) {
            $nextEntity = array_pop($entities);
            $params['nextResource'] = $nextEntity['id'];
            $response['nextCallUrl'] = '/taoSync/SynchronisationApi/fetchEntityChecksums?' . http_build_query([
                SynchronisationApi::PARAM_TYPE => $type,
                SynchronisationApi::PARAM_PARAMETERS => $params,
            ]);
        }

        $response['entities'] = $entities;
        return $response;
    }

    /**
     * Fetch resource properties of given id in $entityIds
     *
     * @param $type
     * @param array $entityIds
     * @param array $options
     * @return array
     * @throws \common_exception_BadRequest
     */
    public function fetchEntityDetails($type, array $entityIds, array $options = [])
    {
        $options = array_merge(['withProperties' => true], $options);
        $entities = [];
        foreach ($entityIds as $id) {
            try {
                $entities[$id] = $this->getSynchronizer($type)->fetchOne($id, $options);
            } catch (\common_exception_NotFound $e) {
            }
        }
        return $entities;
    }

    /**
     * Fetch $requestedClasses classes from a RdfClassSynchronizer by $type
     *
     * @param $type
     * @param array $requestedClasses
     * @return array
     * @throws \common_exception_BadRequest
     * @throws \common_exception_NotImplemented
     */
    public function fetchMissingClasses($type, array $requestedClasses)
    {
        $synchronizer = $this->getSynchronizer($type);
        if (!$synchronizer instanceof RdfClassSynchronizer) {
            throw new \common_exception_NotImplemented();
        }
        return $synchronizer->fetchMissingClasses($requestedClasses);
    }

    /**
     * Synchronize a $type entity
     *
     * Optional $params can be passed to synchronizer at fetch query
     * Remote host is called to fetch entityChecksum and nextCallUrl
     * If a nextCallUrl exists into host response, call it to have the next data chunk
     *
     * @param $type
     * @param array $params
     * @throws \common_Exception
     * @throws \common_exception_BadRequest
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    protected function synchronizeType($type, array $params = [])
    {
        $this->report('Synchronizing "' . $type . '"', LogLevel::INFO);

        $response = $this->getSynchronisationClient()->fetchEntityChecksums($type, $params);

        $remoteEntities = isset($response['entities']) ? $response['entities'] : [];
        $nextCallUrl = isset($response['nextCallUrl']) ? $response['nextCallUrl'] : null;
        $previousCall = null;
        while (true) {
            $this->synchronizeEntities($this->getSynchronizer($type), $remoteEntities, $params);
            if (is_null($nextCallUrl) || $previousCall == $nextCallUrl) {
                break;
            }
            $nextCall = $this->getSynchronisationClient()->callUrl($nextCallUrl);
            $remoteEntities = isset($nextCall['entities']) ? $nextCall['entities'] : [];
            $previousCall = $nextCallUrl;
            $nextCallUrl = isset($nextCall['nextCallUrl']) ? $nextCall['nextCallUrl'] : null;
            if ($previousCall == $nextCallUrl) {
                // Avoid loop if nextCallUrl does not change
                break;
            }
        }

        $this->deleteEntities($type);
    }

    /**
     * Synchronize entities
     *
     * 1 - Fetch and compare local and remote checksum
     * 2 - If not found locally then synchronize it
     * 3 - If not matching checksum then synchronize it
     * 4 - Persist the diff array (['create' => [...], 'update' => [...]])
     *
     * @param Synchronizer $synchronizer
     * @param array $params
     * @param array $remoteEntities
     * @return bool
     * @throws \common_Exception
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     * @throws \common_exception_NotImplemented
     */
    protected function synchronizeEntities(Synchronizer $synchronizer, array $remoteEntities, $params = [])
    {
        $entities = [
            'create' => [],
            'update' => [],
            'existing' => [],
        ];

        if (empty($remoteEntities)) {
            $this->report('(' . $synchronizer->getId() . ') No entity to synchronize.');
            return true;
        }

        foreach ($remoteEntities as $remoteEntity) {
            $id = $remoteEntity['id'];
            try {
                if ($this->isLocalEntityUpToDate($synchronizer, $remoteEntity, $params)) {
                    // up to date
                    $this->report('(' . $synchronizer->getId() . ') Entity "' . $id . '" is already up to date.');
                    $entities['existing'][] = $id;
                } else {
                    // update
                    $entities['update'][] = $remoteEntity;
                    $this->report('(' . $synchronizer->getId() . ') Entity "' . $id . '" does not match. To be synchronized...');
                }
            } catch (\common_exception_NotFound $e) {
                // create
                $entities['create'][] = $remoteEntity;
                $this->report('(' . $synchronizer->getId() . ') Entity "' . $id . '" does not exist. To be synchronized...');
            }
        }

        return $this->persist($synchronizer, $this->getEntityDetails($synchronizer->getId(), $entities, $params));
    }

    private function isLocalEntityUpToDate($synchronizer, $remoteEntity, $params)
    {
        $cachedChecksum = $this->getEntityChecksumCacheService()->get($remoteEntity['id']);
        if (!empty($cachedChecksum) && $cachedChecksum === $remoteEntity['checksum']) {
            return true;
        }
        $localEntity = $synchronizer->fetchOne($remoteEntity['id'], $params);

        return $localEntity['checksum'] == $remoteEntity['checksum'];
    }

    /**
     * Delete entities once the synchronisation process is done.
     *
     * Fetch all entities not processed by synchro. It means that there are not in remote server.
     * Then delete it
     *
     * @param string $type Entity type
     * @throws \common_exception_BadRequest
     * @throws \common_exception_Error
     * @throws \core_kernel_persistence_Exception
     */
    protected function deleteEntities($type)
    {
        $entityIds = $this->getSyncHistoryService()->getNotUpdatedEntityIds($type);
        $this->getSynchronizer($type)->deleteMultiple($entityIds);
        $this->getEntityChecksumCacheService()->delete($entityIds);
        $this->getSyncHistoryService()->logDeletedEntities($type, $entityIds);
        $this->report(count($entityIds) . ' deleted.', LogLevel::INFO);
        if (!empty($entityIds)) {
            $logData = [$type => ['deleted' => count($entityIds)]];
            $this->report->setData(SyncLogDataHelper::mergeSyncData($this->report->getData(), $logData));
        }
    }

    /**
     * Get details for entities
     *
     * Call remote host to have origin entity details
     *
     * @param $type
     * @param array $entities
     * @param array $params
     * @return array
     * @throws \common_Exception
     */
    protected function getEntityDetails($type, array $entities = [], $params = [])
    {
        if (!empty($entities['create'])) {
            $entityIds = [];
            foreach ($entities['create'] as $entity) {
                $entityIds[] = $entity['id'];
            }
            $toCreate = $this->getSynchronisationClient()->fetchEntityDetails($type, $entityIds, $params);
            $entities['create'] = $toCreate;
        }

        if (!empty($entities['update'])) {
            $entityIds = [];
            foreach ($entities['update'] as $entity) {
                $entityIds[] = $entity['id'];
            }
            $toUpdate = $this->getSynchronisationClient()->fetchEntityDetails($type, $entityIds, $params);
            $entities['update'] = $toUpdate;
        }

        return $entities;
    }

    /**
     * Persist entities through synchronizer
     *
     * Entities to update and insert are separated to allow multiple operations
     *
     * @param Synchronizer $synchronizer
     * @param array $entities
     * @return bool
     * @throws \common_exception_Error
     */
    protected function persist(Synchronizer $synchronizer, array $entities)
    {
        $synchronizer->before($entities);

        if (!empty($entities['existing'])) {
            $this->getSyncHistoryService()->logNotChangedEntities($synchronizer->getId(), $entities['existing']);
        }

        $logData = [$synchronizer->getId() => []];
        if (!empty($entities['create'])) {
            $created = $synchronizer->insertMultiple($entities['create']);

            $toCreate = array_column($entities['create'], 'id');
            if (count($created) != count($toCreate)) {
                $notCreated = array_diff($toCreate, $created);
                foreach ($notCreated as $id) {
                    $this->report('(' . $synchronizer->getId() . ') Problem with synchronisation of entity ' . $id, LogLevel::ERROR);
                }
            }

            $this->report('(' . $synchronizer->getId() . ') ' . count($created) . ' entities created.', LogLevel::INFO);
            $logData[$synchronizer->getId()]['created'] = count($created);
            $this->getSyncHistoryService()->logCreatedEntities($synchronizer->getId(), $created);
            $this->getEntityChecksumCacheService()->update($synchronizer, $created);
        }

        if (!empty($entities['update'])) {
            $synchronizer->updateMultiple($entities['update']);
            $this->report('(' . $synchronizer->getId() . ') ' . count($entities['update']) . ' entities updated.', LogLevel::INFO);
            $entityIds = array_column($entities['update'], 'id');
            $logData[$synchronizer->getId()]['updated'] = count($entityIds);
            $this->getSyncHistoryService()->logUpdatedEntities($synchronizer->getId(), $entityIds);
            $this->getEntityChecksumCacheService()->update($synchronizer, $entityIds);
        }
        $this->report->setData(SyncLogDataHelper::mergeSyncData($this->report->getData(), $logData));

        $synchronizer->after($entities);

        return true;
    }

    /**
     * Report a message by log it and add it to $this->report
     *
     * @param $message
     * @param string $level
     * @throws \common_exception_Error
     */
    protected function report($message, $level = LogLevel::DEBUG)
    {
        switch ($level) {
            case LogLevel::INFO:
                $this->logInfo($message);
                $reportLevel = \common_report_Report::TYPE_SUCCESS;
                break;
            case LogLevel::ERROR:
                $this->logError($message);
                $reportLevel = \common_report_Report::TYPE_ERROR;
                break;
            case LogLevel::DEBUG:
            default:
                $this->logDebug($message);
                $reportLevel = \common_report_Report::TYPE_INFO;
                break;
        }
        $this->report->add(new \common_report_Report($reportLevel, $message));
    }

    /**
     * Get all available synchronizer from the config
     *
     * @return Synchronizer[]
     */
    protected function getAllTypes()
    {
        $synchronizers = $this->getOption(self::OPTION_SYNCHRONIZERS);
        return is_array($synchronizers) ? array_keys($synchronizers) : [];
    }

    /**
     * Get the configured chunk
     *
     * @return int
     */
    protected function getChunkSize()
    {
        return $this->hasOption(self::OPTION_CHUNK_SIZE) ? $this->getOption(self::OPTION_CHUNK_SIZE) : self::DEFAULT_CHUNK_SIZE;
    }

    /**
     * @return SynchronisationClient
     */
    protected function getSynchronisationClient()
    {
        return $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
    }

    /**
     * @return DataSyncHistoryService
     */
    protected function getSyncHistoryService()
    {
        return $this->getServiceLocator()->get(DataSyncHistoryService::SERVICE_ID);
    }

    /**
     * @return EventManager
     */
    protected function getEventManager()
    {
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }

    /**
     * Get a synchronizer from config
     *
     * @param string $type Synchronizer type
     * @return Synchronizer
     * @throws \common_exception_BadRequest
     */
    public function getSynchronizer($type)
    {
        if (!isset($this->synchronizers[$type])) {
            if ($this->hasOption(self::OPTION_SYNCHRONIZERS)) {
                $synchronizers = $this->getOption(self::OPTION_SYNCHRONIZERS);
                if (is_array($synchronizers) && array_key_exists($type, $synchronizers)) {
                    $synchronizer = $synchronizers[$type];
                    if (is_object($synchronizer)) {
                        $this->synchronizers[$type] = $this->propagate($synchronizer);
                    } elseif (is_string($synchronizer)) {
                        if (is_a($synchronizer, Synchronizer::class, true)) {
                            $this->synchronizers[$type] = $this->propagate(new $synchronizer());
                        }
                    } elseif (is_array($synchronizer)) {
                        if (isset($synchronizer['class']) && is_a($synchronizer['class'], Synchronizer::class, true)) {
                            $synchronizerClass = $synchronizer['class'];
                            if (isset($synchronizer['options']) && is_array($synchronizer['options'])) {
                                $options = $synchronizer['options'];
                            } else {
                                $options = [];
                            }
                        }
                        $this->synchronizers[$type] = $this->propagate(new $synchronizerClass($options));
                    }
                }
            }
        }

        if (!isset($this->synchronizers[$type])) {
            throw new \common_exception_BadRequest('No synchronizer associated to the given type ');
        }

        return $this->synchronizers[$type];
    }

    /**
     * @param array $params
     * @return int
     * @throws \core_kernel_persistence_Exception
     */
    private function getSyncId(array $params)
    {
        if (empty($params[DataSyncHistoryService::SYNC_NUMBER])) {
            $params[DataSyncHistoryService::SYNC_NUMBER] = $this->getSyncHistoryService()->createSynchronisation($params);
        }

        return $params[DataSyncHistoryService::SYNC_NUMBER];
    }

    private function getEntityChecksumCacheService(): EntityChecksumCacheService
    {
        return $this->getServiceLocator()->get(EntityChecksumCacheService::SERVICE_ID);
    }
}
