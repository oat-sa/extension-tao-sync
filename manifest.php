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

use oat\taoSync\controller\HandShake;
use oat\taoSync\scripts\install\InstallEnhancedDeliveryLog;
use oat\taoSync\scripts\install\RegisterOfflineToOnlineResultMapper;
use oat\taoSync\controller\SynchronizationHistory;
use oat\taoSync\scripts\install\RegisterRdsSyncLogStorage;

return array(
    'name' => 'taoSync',
    'label' => 'Tao Sync',
    'description' => 'TAO synchronisation for offline client data.',
    'license' => 'GPL-2.0',
    'version' => '5.3.0',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'generis'         => '>=7.9.5',
        'tao'             => '>=22.0.0',
        'taoOauth'        => '>=1.0.3',
        'taoTestCenter'   => '>=4.6.0',
        'taoResultServer' => '>=7.0.0',
        'taoTaskQueue'    => '>=1.0.0',
        'taoDelivery'     => '>=12.5.0',
        'taoDeliveryRdf'  => '>=6.0.0',
        'taoProctoring'   => '>=12.7.0',
        'taoPublishing'   => '>=1.3.0',
    ),
    'managementRole' => 'http://www.tao.lu/Ontologies/generis.rdf#taoSyncManager',
    'acl' => [
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoSyncManager', ['ext'=>'taoSync']],
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#taoSyncManager', SynchronizationHistory::class],
        ['grant', 'http://www.tao.lu/Ontologies/generis.rdf#AnonymousRole', HandShake::class],
    ],
    'install' => [
        'rdf' => [
            __DIR__ . '/model/ontology/synchronisation.rdf',
            __DIR__ . '/model/ontology/taosync.rdf',
            __DIR__ . '/model/ontology/VMList.rdf'
        ],
        'php' => [
            \oat\taoSync\scripts\install\RegisterSyncService::class,
            \oat\taoSync\scripts\install\RegisterSyncPublishingAction::class,
            \oat\taoSync\scripts\install\AttachEvents::class,
            \oat\taoSync\scripts\install\RegisterSyncFilesystem::class,
            \oat\taoSync\scripts\install\InstallSynchronisationHistory::class,
            \oat\taoSync\scripts\install\RegisterHandShakeService::class,
            \oat\taoSync\scripts\install\RegisterHandShakeServerService::class,
            \oat\taoSync\scripts\install\SetupSyncUserCsvImporter::class,
            RegisterOfflineToOnlineResultMapper::class,
            InstallEnhancedDeliveryLog::class,
            RegisterRdsSyncLogStorage::class,
            \oat\taoSync\scripts\install\RegisterTestCenterService::class,
        ]
    ],
    'uninstall' => array(
    ),
    'update' => oat\taoSync\scripts\update\Updater::class,
    'routes' => array(
        '/taoSync' => 'oat\\taoSync\\controller'
    ),
    'constants' => array(
        # views directory
        "DIR_VIEWS" => __DIR__ . DIRECTORY_SEPARATOR."views".DIRECTORY_SEPARATOR,

        #BASE URL (usually the domain root)
        'BASE_URL' => ROOT_URL.'taoSync/',
    ),
    'extra' => array(
        'structures' => __DIR__ . DIRECTORY_SEPARATOR.'controller'.DIRECTORY_SEPARATOR.'structures.xml',
    ),
);
