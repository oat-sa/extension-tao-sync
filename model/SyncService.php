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
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\controller\SynchronisationApi;
use oat\taoSync\model\api\SynchronisationClient;
use oat\taoSync\model\synchronizer\Synchronizer;
use Psr\Log\LogLevel;

class SyncService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/syncService';

    const OPTION_SYNCHRONIZERS = 'synchronizers';
    const OPTION_CHUNK_SIZE = 'chunkSize';
    const DEFAULT_CHUNK_SIZE = 100;

    protected $synchronizers = array();

    protected $report;

    public function synchronize($type = null, array $params = [])
    {
        $this->report = \common_report_Report::createInfo('Starting synchronization...');

        if (is_null($type)) {
            foreach($this->getAllTypes() as $type) {
                $this->synchronizeType($type, $params);
            }
        } else {
            $this->synchronizeType($type, $params);
        }

        return $this->report;
    }

    public function fetch($type, $params)
    {
        $response = [
            'type' => $type
        ];

        $options = $params;
        $options['order'] = [
            Entity::CREATED_AT => 'asc',
            OntologyRdfs::RDFS_LABEL => 'asc',
        ];

        $limit = $this->getChunkSize() + 1;
        $options['limit'] = $limit;

        if (isset($options['nextResource'])) {
            $resource = $this->getResource($options['nextResource']);
            $startEpoch = $resource->getOnePropertyValue($this->getProperty(Entity::CREATED_AT));
            if (!is_null($startEpoch)) {
                $options['startCreatedAt'] = $startEpoch->literal;
            }
        }

        $entities = $this->getSynchronizer($type)->fetch($options);

        if (count($entities) == $limit) {
            $nextEntity = array_pop($entities);
            $params['nextResource'] = $nextEntity['id'];
            $response['nextCallUrl'] = '/taoSync/SynchronisationApi/fetch?' . http_build_query(['type' => $type, SynchronisationApi::PARAMS => $params]);
        }

        $response['entities'] = $entities;
        return $response;
    }

    protected function synchronizeType($type, array $params = [])
    {
        $this->report('About synchronization for type "' . $type .'"', LogLevel::INFO);

        $response = $this->getSynchronisationClient()->fetch($type, $params);

        $remoteEntities = isset($response['entities']) ? $response['entities'] : [];
        $nextCallUrl = isset($response['nextCallUrl']) ? $response['nextCallUrl'] : null;

        while (true) {
            if (empty($remoteEntities)) {
                break;
            }
            $this->synchronizeEntities($this->getSynchronizer($type), $remoteEntities, $params);

            if (is_null($nextCallUrl)) {
                break;
            }
            $nextCall = $this->getSynchronisationClient()->callUrl($nextCallUrl);
            $remoteEntities = isset($nextCall['entities']) ? $nextCall['entities'] : [];
            $nextCallUrl = isset($nextCall['nextCallUrl']) ? $nextCall['nextCallUrl'] : null;
        }
    }

    protected function synchronizeEntities(Synchronizer $synchronizer, array $remoteEntities)
    {
        $entities = array(
            'create' => [],
            'update' => [],
        );

        if (empty($remoteEntities)) {
            $this->report('(' . $synchronizer->getId() . ') No entity to synchronize.');
            return true;
        }

        foreach ($remoteEntities as $remoteEntity) {
            $id = $remoteEntity['id'];
            try {
                $localEntity = $synchronizer->fetchOne($id);
                if ($localEntity['checksum'] == $remoteEntity['checksum']) {
                    // up to date
                    $this->report('(' . $synchronizer->getId() . ') Entity "' . $id . '" is already up to date.');
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

        return $this->persist($synchronizer, $entities);
    }

    protected function persist(Synchronizer $synchronizer, array $entities)
    {
        $synchronizer->before($entities);

        if (!empty($entities['create'])) {
            $synchronizer->insertMultiple($entities['create']);
            $this->report('(' . $synchronizer->getId() . ') ' . count($entities['create']) . ' entities created.');
        }

        if (!empty($entities['update'])) {
            $synchronizer->updateMultiple($entities['update']);
            $this->report('(' . $synchronizer->getId() . ') ' . count($entities['update']) . ' entities updated.');
        }
//        $this->getSynchronizer($type)->deleteMultiple($entities['delete']);

        $synchronizer->after($entities);

        return true;
    }

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

    protected function getNextParams(array $params)
    {

    }

    protected function getAllTypes()
    {
        $synchronizers = $this->getOption(self::OPTION_SYNCHRONIZERS);
        return is_array($synchronizers) ? array_keys($synchronizers) : [];
    }

    protected function getChunkSize()
    {
        return $this->hasOption(self::OPTION_CHUNK_SIZE) ? $this->getOption(self::OPTION_CHUNK_SIZE) : self::DEFAULT_CHUNK_SIZE;
    }

    public function fetchMissingClasses($type, array $requestedClasses)
    {
        $synchronizer = $this->getSynchronizer($type);
//        if ($synchronizer instanceof RdfSynchronizer) {
//            throw new \common_exception_NotImplemented();
//        }
        return $synchronizer->fetchMissingClasses($requestedClasses);
    }

    /**
     * @return SynchronisationClient
     */
    protected function getSynchronisationClient()
    {
        return $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
    }

    /**
     * @param $type
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

}