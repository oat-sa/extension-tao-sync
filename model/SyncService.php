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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\TaoOntology;
use oat\taoSync\model\api\SynchronisationApi;
use oat\taoSync\model\api\SynchronisationClient;
use oat\taoSync\model\synchronizer\TestTakerSynchronizer;

class SyncService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/syncService';

    public function synchronizeData($options)
    {
        $classUri = TaoOntology::CLASS_URI_SUBJECT;

        $resourcesToUpdate = $resourcesToAdd = $childrenToUpdate = [];

        $localResources = $this->getLocalClassTree($classUri);
        $remoteResources = $this->getRemoteClassTreeChecksum($classUri);

        var_dump($remoteResources);
        foreach ($remoteResources as $remoteResource) {
            if (array_key_exists($remoteResource['uri'], $localResources)) {
                $localResource = $localResources[$remoteResource['uri']];
                if ($remoteResource['checksum'] != $localResource['checksum']) {
                    $resourcesToUpdate[$remoteResource['uri']] = $remoteResource;
                }
                if (isset($remoteResource['childrenChecksum']) && isset($localResource['childrenChecksum'])
                    && $remoteResource['childrenChecksum'] != $localResource['childrenChecksum']) {
                    $childrenToUpdate[$remoteResource['uri']] = $remoteResource;
                }
            } else {
                $resourcesToAdd[$remoteResource['uri']] = $remoteResource;
            }
            unset($localResources[$remoteResource['uri']]);
        }

        $this->removeResources($localResources);
        $this->updateResources($resourcesToUpdate);
        $this->addResources($resourcesToAdd);
    }

    public function getLocalClassTree($classUri)
    {
        $class = $this->getClass($classUri);
        $values = [];

        /** @var \core_kernel_classes_Resource $resource */
        foreach ($class->getInstances() as $resource) {
            $value['uri'] = $resource->getUri();
            $value['type'] = 'resource';
            $value['checksum'] = md5(serialize($resource->getRdfTriples()->toArray()));
            $value['childrenChecksum'] = null;
            $value['properties'] = $resource->getRdfTriples()->toArray();
            $values[$resource->getUri()] = $value;
        }

        /** @var \core_kernel_classes_Class $subClass */
        foreach ($class->getSubClasses() as $subClass) {
            $value['uri'] = $resource->getUri();
            $value['type'] = 'class';
            $value['checksum'] = md5(serialize($subClass->getRdfTriples()->toArray()));
            $value['childrenChecksum'] = '';//md5(serialize($resource->getRdfTriples()->toArray()));
            $value['properties'] = $resource->getRdfTriples()->toArray();
            $values[$resource->getUri()] = $value;
        }

        return $values;
    }

    protected function getRemoteClassTreeChecksum($classUri)
    {
        $client = $this->getServiceLocator()->get(SynchronisationClient::SERVICE_ID);
        return $client->getRemoteClassTree($classUri);
    }

    /**
     * @param array $resources
     */
    protected function addResources(array $remoteResources = [])
    {
        foreach ($remoteResources as $remoteResource) {
            \common_Logger::e(print_r(__FUNCTION__, true));
            \common_Logger::i(print_r($remoteResource, true));
            continue;
            $resource = $this->getResource($remoteResource['uri']);
            if ($resource->isClass()) {
                /** @var \core_kernel_classes_Class $resource */
                $subResources = $resource->getInstances();
                $this->synchronizeData($subResources);
            }
        }

//        $remoteResourceProperties = $this->getRemoteInstances($remoteResources);
//        $this->insertMultiples($remoteResourceProperties);
    }

    /**
     * @param array $resources
     */
    protected function updateResources(array $remoteResources = [])
    {
        foreach ($remoteResources as $remoteResource) {
            \common_Logger::e(print_r(__FUNCTION__, true));
            \common_Logger::i(print_r($remoteResource, true));
            continue;
            $resource = $this->getResource($remoteResource['uri']);
            if ($resource->isClass()) {
                /** @var \core_kernel_classes_Class $resource */
                $subResources = $resource->getInstances();
                $this->synchronizeData($subResources);
            }
        }

//        $remoteResourceProperties = $this->getRemoteInstances($remoteResources);
//        $this->updateMultiples($remoteResourceProperties);
    }

    /**
     * @param array $resources
     */
    protected function removeResources(array $resources = [])
    {
        foreach ($resources as $resource) {
            \common_Logger::e(print_r(__FUNCTION__, true));
            \common_Logger::i(print_r($resource, true));
            continue;
            $this->getResource($resource['uri'])->delete();
        }
    }

    protected function getSynchronisationApi()
    {
        $this->getServiceLocator()->get(SynchronisationApi::SERVICE_ID);
    }

    protected function getSynchronizers()
    {
        return [
            new TestTakerSynchronizer()
        ];
    }
}