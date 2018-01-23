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
use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\api\SynchronisationClient;
use oat\taoSync\model\synchronizer\Synchronizer;

class SyncService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/syncService';

    const OPTION_SYNCHRONIZERS = 'synchronizers';

    protected $synchronizers = array();

    public function synchronize($type = null, array $options = [])
    {
        $allTypes = [
            'test-center',
            'administrator',
            'proctor',
            'test-taker',
            'eligibility',
            'delivery',
//            'sub-test-center'
        ];

        $report = \common_report_Report::createInfo('Starting synchronization...');

        if (is_null($type)) {
            foreach($allTypes as $type) {
                $report->add($this->synchronizeType($type, $options));
            }
        } else {
            $report->add($this->synchronizeType($type, $options));
        }

        return $report;
    }

    protected function synchronizeType($type, array $options = [])
    {
        $limit = isset($options['limit']) ? $options['limit'] : 100;
        $offset = isset($options['offset']) ? $options['offset'] : 0;
        $options['limit'] = $limit;
        $options['offset'] = $offset;

        $report = \common_report_Report::createSuccess('Synchronizing type "' . $type . '"');

        $count = $this->getRemoteCount($type, $options);

        $entities = array(
            'create' => [],
            'update' => [],
            'delete' => [],
            'exist' => [],
        );

//        \common_Logger::i('remote count : ' . $count);
        $insertOffset = $offset;
        while ($count > 0) {
            $options['offset'] = $insertOffset;
            $remoteInstances = $this->getRemoteInstances($type, $options);

            foreach ($remoteInstances as $remoteInstance) {
//                \common_Logger::i('Remote instance :: ' . print_r($remoteInstance,true));

                $id = $remoteInstance['id'];
                $entities['exist'][$id] = $id;
                try {
                    $localInstance = $this->getSynchronizer($type)->fetchOne($id, $options);
//                    \common_Logger::i('Local instance :: ' . print_r($localInstance,true));
                    if ($localInstance['checksum'] != $remoteInstance['checksum']) {
                        //TO UPDATE
                        $entities['update'][$id] = $remoteInstance;
                        $report->add(\common_report_Report::createInfo('Resource "' . $id . '" does not match, UPDATE'));
                    } else {
                        $report->add(\common_report_Report::createInfo('Resource "' . $id . '" is up to date, SYNC'));
                    }
                } catch (\common_exception_NotFound $e) {
                    //TO CREATE
                    $entities['create'][$id] = $remoteInstance;
                }

            }
            $count -= $limit;
            $insertOffset += $limit;
        }

        /*
         * Delete entities existing on remote but no locally
         * Waiting for business rules
         */
        $count = $this->count($type, $options);
        $deleteOffset = $offset;
        while ($count > 0) {
            $options['offset'] = $deleteOffset;
            $resources = $this->fetchAll($type);
            foreach ($resources as $resource) {
                if (!in_array($resource['id'], $entities['exist'])) {
                    $entities['delete'][] = $resource['id'];
                    $report->add(\common_report_Report::createInfo('Resource "' . $resource['id'] . '" does not exist anymore, DELETE'));
                }
            }
            $count -= $limit;
            $deleteOffset += $limit;
        }

        $report->add(\common_report_Report::createInfo('Resource to DELETE : ' . count($entities['delete'])));
        $report->add(\common_report_Report::createInfo('Resource to CREATE : ' . count($entities['create'])));
        $report->add(\common_report_Report::createInfo('Resource to UPDATE : ' . count($entities['update'])));

        $this->getSynchronizer($type)->before($entities);
        $this->getSynchronizer($type)->deleteMultiple($entities['delete']);
        $this->getSynchronizer($type)->insertMultiple($entities['create']);
        $this->getSynchronizer($type)->updateMultiple($entities['update']);
        $this->getSynchronizer($type)->after($entities);


        return $report;
    }

    public function fetch($type, $options)
    {
        return $this->getSynchronizer($type)->fetch($options);
    }

    public function fetchAll($type)
    {
        return $this->getSynchronizer($type)->fetchAll();
    }

    public function count($type, $options)
    {
        return $this->getSynchronizer($type)->count($options);
    }

    public function fetchMissingClasses($type, array $requestedClasses)
    {
        $synchronizer = $this->getSynchronizer($type);
//        if ($synchronizer instanceof RdfSynchronizer) {
//            throw new \common_exception_NotImplemented();
//        }
        return $synchronizer->fetchMissingClasses($requestedClasses);
    }

    protected function getRemoteInstances($type, array $options = [])
    {
        return $this->getSynchronisationClient()->fetchRemoteEntities($type, $options);
    }

    protected function getRemoteCount($type, $options)
    {
        return $this->getSynchronisationClient()->count($type, $options);
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