<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

/**
 * Fixture data (choiceFilter field):
 * - ID 1: 'option_a'
 * - ID 2: 'option_b'
 * - ID 3: 'option_c'
 * - ID 4: 'option_a'
 * - ID 5: 'option_b'
 * - ID 6: 'option_c'
 */
class ChoiceFilterTest extends FilterFunctionalTestCase
{
    /**
     * @dataProvider equalsProvider
     */
    public function testFilterEquals(string $option, array $expectedIds): void
    {
        $url = $this->generateFilteredIndexUrl([
            'choiceFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => $option,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(\count($expectedIds));
        $this->assertVisibleEntityIds($expectedIds);
    }

    public static function equalsProvider(): iterable
    {
        yield 'matches option_a' => ['option_a', [1, 4]];
        yield 'matches option_b' => ['option_b', [2, 5]];
        yield 'matches option_c' => ['option_c', [3, 6]];
    }

    public function testFilterNotEquals(): void
    {
        // filter for choiceFilter != 'option_a'
        // should match: ID 2, ID 3, ID 5, ID 6
        $url = $this->generateFilteredIndexUrl([
            'choiceFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => 'option_a',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntityIds([2, 3, 5, 6]);
    }

    public function testFilterIsNull(): void
    {
        // filter for NULL choices
        // when value is null/empty, the filter uses IS NULL comparison
        // since our fixture data has no null choiceFilter values, we test
        // that the filter is properly applied (returns 0 results)
        $url = $this->generateFilteredIndexUrl([
            'choiceFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '',
            ],
        ]);

        $this->client->request('GET', $url);

        // all our fixture data has non-null choice values, so IS NULL returns 0
        $this->assertFilteredCount(0);
    }
}
