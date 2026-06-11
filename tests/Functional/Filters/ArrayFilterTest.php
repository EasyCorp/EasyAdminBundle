<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

/**
 * Fixture data (arrayFilter field):
 * - ID 1: ['tag1', 'tag2']
 * - ID 2: ['tag2', 'tag3']
 * - ID 3: ['tag1']
 * - ID 4: ['tag3']
 * - ID 5: ['tag1', 'tag2', 'tag3']
 * - ID 6: []
 */
class ArrayFilterTest extends FilterFunctionalTestCase
{
    /**
     * @dataProvider containsSingleValueProvider
     */
    public function testFilterContainsSingleValue(string $tag, array $expectedIds): void
    {
        $url = $this->generateFilteredIndexUrl([
            'arrayFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => [$tag],
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedIds));
        $this->assertVisibleEntityIds($expectedIds);
    }

    public static function containsSingleValueProvider(): iterable
    {
        yield 'matches tag1' => ['tag1', [1, 3, 5]];
        yield 'matches tag2' => ['tag2', [1, 2, 5]];
        yield 'matches tag3' => ['tag3', [2, 4, 5]];
    }

    public function testFilterNotContains(): void
    {
        // filter for arrayFilter NOT containing 'tag1'
        // should match: ID 2, ID 4, ID 6
        $url = $this->generateFilteredIndexUrl([
            'arrayFilter' => [
                'comparison' => ComparisonType::NOT_CONTAINS,
                'value' => ['tag1'],
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([2, 4, 6]);
    }

    /**
     * @dataProvider containsAllProvider
     */
    public function testFilterContainsAll(array $tags, array $expectedIds): void
    {
        $url = $this->generateFilteredIndexUrl([
            'arrayFilter' => [
                'comparison' => ComparisonType::CONTAINS_ALL,
                'value' => $tags,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedIds));
        $this->assertVisibleEntityIds($expectedIds);
    }

    public static function containsAllProvider(): iterable
    {
        yield 'matches tag1 and tag2' => [['tag1', 'tag2'], [1, 5]];
        yield 'matches all three tags' => [['tag1', 'tag2', 'tag3'], [5]];
    }
}
