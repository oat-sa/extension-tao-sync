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

namespace oat\taoSync\model\synchronizer\custom\byOrganisationId;

use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;

trait OrganisationIdTrait
{
    /**
     * Extract the organisation id parameter from $parameters
     *
     * @param array $options
     * @return string The organisation id
     * @throws \common_exception_NotFound If does not exist
     */
    protected function getOrganisationIdFromOption(array $options = [])
    {
        if (!isset($options[TestCenterByOrganisationId::OPTION_ORGANISATION_ID])) {
            $this->logError('Organisation id cannot be retrieved from parameters. Current synchronisation aborted.');
            throw new \common_exception_NotFound();
        }
        return (string) $options[TestCenterByOrganisationId::OPTION_ORGANISATION_ID];
    }
}