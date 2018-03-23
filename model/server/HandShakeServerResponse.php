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

namespace oat\taoSync\model\server;

use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\oauth\DataStore;
use oat\taoOauth\model\storage\ConsumerStorage;
use oat\taoSync\model\formatter\FormatterService;
use oat\taoSync\model\formatter\SynchronizerFormatter;

class HandShakeServerResponse
{
    use OntologyAwareTrait;

    /** @var \core_kernel_classes_Resource */
    protected $consumerOauth;

    /** @var \core_kernel_classes_Resource */
    protected $user;

    /** @var SynchronizerFormatter */
    protected $formatter;

    /**
     * HandShakeServerResponse constructor.
     * @param \core_kernel_classes_Resource $consumerOauth
     * @param \core_kernel_classes_Resource $user
     * @param SynchronizerFormatter $formatter
     */
    public function __construct(
        \core_kernel_classes_Resource $consumerOauth,
        \core_kernel_classes_Resource $user,
        SynchronizerFormatter $formatter
    ){
        $this->consumerOauth = $consumerOauth;
        $this->user = $user;
        $this->formatter = $formatter;
    }

    /**
     * @return array
     * @throws \common_Exception
     * @throws \core_kernel_classes_EmptyProperty
     */
    public function asArray()
    {
        return [
            'syncUser' => $this->formatter->format($this->user, [FormatterService::OPTION_INCLUDED_PROPERTIES => true]),
            'oauthInfo' => [
                'key' => $this->consumerOauth->getUniquePropertyValue($this->getProperty(DataStore::PROPERTY_OAUTH_KEY))->literal,
                'secret' => $this->consumerOauth->getUniquePropertyValue($this->getProperty(DataStore::PROPERTY_OAUTH_SECRET))->literal,
                'tokenUrl' => $this->consumerOauth->getUniquePropertyValue($this->getProperty(ConsumerStorage::CONSUMER_TOKEN_URL))->literal,
            ]
        ];
    }
}