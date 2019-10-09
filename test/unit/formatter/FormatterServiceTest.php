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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

namespace oat\taoSync\test\unit\formatter;

use oat\taoSync\model\formatter\FormatterService;
use oat\generis\test\MockObject;

class FormatterServiceTest extends \oat\generis\test\TestCase
{
    /** @var FormatterService */
    private $service;

    /** @var array */
    private $tripleFixture;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new FormatterService();
        $this->tripleFixture = $this->getTripleFixture();
    }

    public function testOnlyFields()
    {
        // when only-fields option is provided then other fields are filtered out
        $resource = $this->mockResource('uri', $this->createTripleCollection($this->tripleFixture));
        $options = [
            FormatterService::OPTION_INCLUDED_PROPERTIES => true,
            FormatterService::OPTION_ONLY_FIELDS => ['http://ontologies/TAOProctor.rdf#EligibileDelivery'],
        ];
        $result = $this->service->format($resource, $options);
        $this->assertEquals(['http://ontologies/TAOProctor.rdf#EligibileDelivery'], array_keys($result['properties']));
    }

    public function testExcludedFields()
    {
        // when excluded-fields option is provided then provided fields are filtered out
        $resource = $this->mockResource('uri', $this->createTripleCollection($this->tripleFixture));
        $options = [
            FormatterService::OPTION_INCLUDED_PROPERTIES => true,
            FormatterService::OPTION_EXCLUDED_FIELDS => ['http://ontologies/TAO.rdf#CreatedAt'],
        ];
        $result = $this->service->format($resource, $options);
        $this->assertNotEmpty($result['properties']);
        $this->assertArrayNotHasKey('http://ontologies/TAO.rdf#CreatedAt', $result['properties']);
    }

    public function testIncludedProperties()
    {
        // when with-properties option is provided then result includes the properties
        $resource = $this->mockResource('uri', $this->createTripleCollection($this->tripleFixture));
        $result = $this->service->format($resource, [FormatterService::OPTION_INCLUDED_PROPERTIES => true]);
        $this->assertNotEmpty($result['properties'], 'Result should contain properties');
        // when with-properties option is not provided then result does not include the properties
        $result = $this->service->format($resource, [FormatterService::OPTION_INCLUDED_PROPERTIES => false]);
        $this->assertEmpty($result['properties'], 'Result should not contain properties');
    }

    public function testHashProperties()
    {
        // hash should be the same for same data with different order
        $resource = $this->mockResource('uri', $this->createTripleCollection($this->tripleFixture));
        $result1 = $this->service->format($resource);
        $resourceWithPropertiesReversed = $this->mockResource('uri', $this->createTripleCollection(array_reverse($this->tripleFixture)));
        $result2 = $this->service->format($resourceWithPropertiesReversed);
        $this->assertEquals($result1['checksum'], $result2['checksum'], 'Checksum for the same properties must be the same.');

        // hash should be different with different property set
        $modifiedTripleFixture = array_merge($this->tripleFixture, [[
            'predicate' => 'http://ontologies/TAOProctor.rdf#EligibileTestTaker',
            'object' => 'http://sample/first.rdf#i15507614087640001',
        ]]);
        $resourceModified = $this->mockResource('uri', $this->createTripleCollection($modifiedTripleFixture));
        $result3 = $this->service->format($resourceModified);

        $this->assertNotEquals($result1['checksum'], $result3['checksum'], 'Checksum for different property set must be different.');
    }

    /**
     * @param array $tripleFixtures
     * @return \core_kernel_classes_ContainerCollection
     */
    private function createTripleCollection($tripleFixtures)
    {
        $collection = new \core_kernel_classes_ContainerCollection(new \common_Object());

        foreach ($tripleFixtures as $tripleFixture) {
            $rdfsTriple = new \core_kernel_classes_Triple();
            $rdfsTriple->predicate = $tripleFixture['predicate'];
            $rdfsTriple->object = $tripleFixture['object'];
            $collection->add($rdfsTriple);
        }

        return $collection;
    }

    /**
     * @param string $uri
     * @param \core_kernel_classes_ContainerCollection $collectionOfTriples
     * @return MockObject
     */
    private function mockResource($uri, $collectionOfTriples)
    {
        $mock = $this->getMockBuilder(\core_kernel_classes_Resource::class)
            ->setMethods(['getUri', 'getRdfTriples'])
            ->disableOriginalConstructor()->getMock();

        $mock->method('getUri')->willReturn($uri);
        $mock->method('getRdfTriples')->willReturn($collectionOfTriples);

        return $mock;
    }

    /**
     * @return array
     */
    private function getTripleFixture()
    {
        return [
            [
                'predicate' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                'object' => 'http://ontologies/TAOProctor.rdf#DeliveryEligibility',
            ],
            [
                'predicate' => 'http://ontologies/TAOProctor.rdf#EligibileDelivery',
                'object' => 'http://sample/first.rdf#i15507616805170217',
            ],
            [
                'predicate' => 'http://ontologies/TAOProctor.rdf#EligibileTestCenter',
                'object' => 'http://sample/first.rdf#i1550664798331824',
            ],
            [
                'predicate' => 'http://ontologies/TAOProctor.rdf#EligibileTestTaker',
                'object' => 'http://sample/first.rdf#i15507614087641208',
            ],
            [
                'predicate' => 'http://ontologies/TAOProctor.rdf#EligibileTestTaker',
                'object' => 'http://sample/first.rdf#i15507613648759207',
            ],
            [
                'predicate' => 'http://ontologies/TAO.rdf#CreatedAt',
                'object' => '1550828200.907',
            ],
        ];
    }
}
