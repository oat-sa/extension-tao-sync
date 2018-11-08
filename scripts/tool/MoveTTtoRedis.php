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
 * Copyright (c) 2018 Open Assessment Technologies SA
 */
namespace oat\taoSync\scripts\tool;

use oat\generis\model\OntologyAwareTrait;
use oat\generis\model\user\UserRdf;
use oat\oatbox\extension\script\ScriptAction;
use common_report_Report as Report;
use oat\tao\model\TaoOntology;
use oat\tao\model\user\TaoRoles;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\OrganisationIdTrait;
use oat\taoTestCenter\model\EligibilityService;

class MoveTTtoRedis extends ScriptAction
{
    use OrganisationIdTrait;
    use OntologyAwareTrait;
    /**
     * @param $params
     * @return Report
     * @throws \Exception
     */
    public function run()
    {
        $limit = $this->getOption('limit');
        $chunk = $this->getOption('chunk');
        $organisationId = $this->getOption('organisation_id');
        $force = $this->getOption('force');
        $merge = $this->getOption('merge');
        $offset = $this->getOption('offset');
        $report = Report::createInfo('Mapping TestTakers Redis');

        $class = $this->getClass(TaoOntology::CLASS_URI_SUBJECT);

        $count = 0;
        $redisTable = new RedisTable();
        $this->propagate($redisTable);

        do {
            if (!is_null($organisationId)) {
                $eligibilities = $this->getEligibilitiesByOrganisationId($organisationId);
                $testtakerResources = [];

                /** @var \core_kernel_classes_Resource $eligibility */
                foreach ($eligibilities as $eligibility) {
                    $tTakers = $eligibility->getPropertyValues($this->getProperty(EligibilityService::PROPERTY_TESTTAKER_URI));

                    foreach ($tTakers as $testTaker) {
                        $testtakerResources[] = $this->getResource($testTaker);
                    }
                }

                $results = array_slice($testtakerResources, $offset, $limit);
            } else {
                $results = $class->searchInstances(
                    [
                        UserRdf::PROPERTY_ROLES => TaoRoles::DELIVERY,
                    ],
                    [
                        'like' => false,
                        'limit' => $limit,
                        'offset' => $offset
                    ]
                );
            }

            if(empty($results)) {
                $report->add(Report::createSuccess('No TT found.'));
                break;
            }

            foreach ($results as $result) {
                if ($chunk !== null & $count === $chunk) {
                    $results = [];
                    break;
                }

                try {
                    $key =  $result->getUri();
                    $values = serialize($result->getRdfTriples()->toArray()) ;

                    if ($force === true)  {
                        if ($merge === true) {
                            $previousRdfTriples = unserialize($redisTable->get($key)) ;
                            $values = array_merge($previousRdfTriples, $values);
                        }

                        $redisTable->set($key,$values);
                        $redisTable->cleanTTInfo($result);
                        $count++;

                    } elseif ($redisTable->has($key) == false) {
                        $redisTable->set($key,$values);
                        $redisTable->cleanTTInfo($result);
                        $count++;
                    }
                } catch (\Exception $e) {
                    $report->add(Report::createFailure($e->getMessage()));
                }
            }
            $offset = $offset + $this->getOption('limit');
        }while(!empty($results));

        $report->add(Report::createSuccess('TT count mapped: ' . $count));
        return $report;
    }

    protected function provideOptions()
    {
        return [
            'limit' => [
                'prefix'      => 'l',
                'longPrefix'  => 'limit',
                'cast'        => 'integer',
                'required'    => true,
                'description' => 'Limit to get tt.'
            ],
            'chunk' => [
                'prefix'      => 'c',
                'longPrefix'  => 'chunk',
                'cast'        => 'integer',
                'required'    => false,
                'description' => 'chunk to get tt.'
            ],
            'organisation_id' => [
                'prefix'      => 'o',
                'longPrefix'  => 'organisation_id',
                'cast'        => 'string',
                'required'    => false,
                'description' => 'organisation_id'
            ],
            'force' => [
                'prefix'      => 'f',
                'longPrefix'  => 'force',
                'cast'        => 'bool',
                'required'    => false,
                'description' => 'force remap'
            ],
            'merge' => [
                'prefix'      => 'm',
                'longPrefix'  => 'merge',
                'cast'        => 'bool',
                'required'    => false,
                'description' => 'merge test taker with redis'
            ],
            'offset' => [
                'prefix'      => 'o',
                'longPrefix'  => 'offset',
                'cast'        => 'integer',
                'required'    => true,
                'description' => 'Offset to get tt.'
            ]
        ];
    }
    protected function provideDescription()
    {
        return 'Mapping TestTakers Redis';
    }
}