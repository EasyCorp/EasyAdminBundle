<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

/**
 * Fixture data (textFilter field):
 * - ID 1: 'alpha test string'
 * - ID 2: 'beta sample text'
 * - ID 3: 'gamma record'
 * - ID 4: 'alphabetical order'
 * - ID 5: 'delta entry'
 * - ID 6: 'this ends with test'
 */
class TextFilterTest extends FilterFunctionalTestCase
{
    public function testFilterContains(): void
    {
        // filter for records containing "alpha"
        // should match: ID 1 ('alpha test string'), ID 4 ('alphabetical order')
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'alpha',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([1, 4]);
    }

    public function testFilterContainsPartialMatch(): void
    {
        // filter for records containing "test"
        // should match: ID 1 ('alpha test string'), ID 6 ('this ends with test')
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'test',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([1, 6]);
    }

    public function testFilterNotContains(): void
    {
        // filter for records NOT containing "alpha"
        // should match all except ID 1 and ID 4
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::NOT_CONTAINS,
                'value' => 'alpha',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntityIds([2, 3, 5, 6]);
    }

    public function testFilterStartsWith(): void
    {
        // filter for records starting with "alpha"
        // should match: ID 1 ('alpha test string'), ID 4 ('alphabetical order')
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::STARTS_WITH,
                'value' => 'alpha',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([1, 4]);
    }

    public function testFilterEndsWith(): void
    {
        // filter for records ending with "test"
        // should match: ID 6 ('this ends with test')
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::ENDS_WITH,
                'value' => 'test',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntityIds([6]);
    }

    public function testFilterEquals(): void
    {
        // filter for exact match
        // should match only ID 3
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => 'gamma record',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntityIds([3]);
    }

    public function testFilterNotEquals(): void
    {
        // filter for records NOT equal to 'gamma record'
        // should match all except ID 3
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => 'gamma record',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntityIds([1, 2, 4, 5, 6]);
    }

    public function testFilterCaseInsensitiveContains(): void
    {
        // filter with different case - should still match (database dependent)
        // most databases do case-insensitive LIKE by default
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'ALPHA',
            ],
        ]);

        $this->client->request('GET', $url);

        // this behavior may vary by database
        // SQLite is case-insensitive by default for ASCII characters
        $this->assertFilteredCount(2);
    }

    public function testFilterNoResults(): void
    {
        // filter for a value that doesn't exist
        $url = $this->generateFilteredIndexUrl([
            'textFilter' => [
                'comparison' => ComparisonType::CONTAINS,
                'value' => 'nonexistent_value_xyz',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(0);
    }
}
