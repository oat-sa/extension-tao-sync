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
use oat\taoSync\model\synchronizer\TestTakerSynchronizer;

class SyncService extends ConfigurableService
{
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoSync/SyncService';

    public function synchronizeData($options)
    {
        $classUri = TaoOntology::CLASS_URI_SUBJECT;

        $resourcesToUpdate = $resourcesToAdd = $childrenToUpdate = [];

        $localResources = $this->getLocalClassTree($classUri);
        var_dump($localResources);die();
        $remoteResources = $this->getRemoteClassTreeChecksum($classUri);
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

    protected function getLocalClassTree($classUri)
    {
        $resources = $this->getClass($classUri)->getInstances();
        $values = [];
        /** @var \core_kernel_classes_Resource $resource */
        foreach ($resources as $resource) {
            if ($resource->isClass()) {
                $values[] = $this->getLocalClassTree($classUri);
            }
            $value['checksum'] = md5(serialize($resource->getRdfTriples()->toArray()));
            $value['childrenChecksum'] = '';//md5(serialize($resource->getRdfTriples()->toArray()));
            $value['properties'] = [];
            $values[$resource->getUri()] = $value;
        }

        return $values;
    }

    /**
     * @param array $resources
     */
    protected function addResources(array $remoteResources = [])
    {
        foreach ($remoteResources as $remoteResource) {

            $resource = $this->getResource($remoteResource['uri']);
            if ($resource->isClass()) {
                /** @var \core_kernel_classes_Class $resource */
                $subResources = $resource->getInstances();
                $this->synchronizeData($subResources);
            }
        }

        $remoteResourceProperties = $this->getRemoteInstances($remoteResources);
        $this->insertMultiples($remoteResourceProperties);
    }

    /**
     * @param array $resources
     */
    protected function updateResources(array $remoteResources = [])
    {
        foreach ($remoteResources as $remoteResource) {

            $resource = $this->getResource($remoteResource['uri']);
            if ($resource->isClass()) {
                /** @var \core_kernel_classes_Class $resource */
                $subResources = $resource->getInstances();
                $this->synchronizeData($subResources);
            }
        }

        $remoteResourceProperties = $this->getRemoteInstances($remoteResources);
        $this->updateMultiples($remoteResourceProperties);
    }

    /**
     * @param array $resources
     */
    protected function removeResources(array $resources = [])
    {
        foreach ($resources as $resource) {
            $this->getResource($resource['uri'])->delete();
        }
    }

    protected function getSynchronizers()
    {
        return [
            new TestTakerSynchronizer()
        ];
    }
}