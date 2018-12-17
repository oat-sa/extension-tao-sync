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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\model\report;

/**
 * Class SynchronizationReport
 * @package oat\taoSync\model\report
 */
class SynchronizationReport extends \common_report_Report
{
    const AMOUNT = 'amount';
    const ENTITIES = 'entities';

    const ACTION_SUCCESSFUL_UPLOAD = 'upload_successful';
    const ACTION_FAILED_UPLOAD = 'upload_failed';

    /**
     * @param string $dataType
     * @param string $action
     * @param array $entities
     * @return $this
     */
    public function addSyncData($dataType, $action, $entities = [])
    {
        $reportData = $this->getData();
        $currentAmount = $reportData[$dataType][$action][self::AMOUNT] ?: 0;
        $currentEntities = $reportData[$dataType][$action][self::ENTITIES] ?: [];

        $reportData[$dataType][$action] = [
            self::AMOUNT    => $currentAmount + count($entities),
            self::ENTITIES  => array_merge($currentEntities, $entities)
        ];

        $this->setData($reportData);

        return $this;
    }
}
