<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

/**
 * Fixture data (numericFilter field):
 * - ID 1: 100
 * - ID 2: 200
 * - ID 3: 50
 * - ID 4: 150
 * - ID 5: 175
 * - ID 6: 300
 */
class NumericFilterTest extends FilterFunctionalTestCase
{
    public function testFilterEquals(): void
    {
        // filter for numericFilter = 100
        // should match: ID 1
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '100',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntityIds([1]);
    }

    public function testFilterNotEquals(): void
    {
        // filter for numericFilter != 100
        // should match all except ID 1
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => '100',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntityIds([2, 3, 4, 5, 6]);
    }

    public function testFilterGreaterThan(): void
    {
        // filter for numericFilter > 150
        // should match: ID 2 (200), ID 5 (175), ID 6 (300)
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => '150',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([2, 5, 6]);
    }

    public function testFilterGreaterThanOrEqual(): void
    {
        // filter for numericFilter >= 150
        // should match: ID 2 (200), ID 4 (150), ID 5 (175), ID 6 (300)
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::GTE,
                'value' => '150',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntityIds([2, 4, 5, 6]);
    }

    public function testFilterLessThan(): void
    {
        // filter for numericFilter < 150
        // should match: ID 1 (100), ID 3 (50)
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::LT,
                'value' => '150',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([1, 3]);
    }

    public function testFilterLessThanOrEqual(): void
    {
        // filter for numericFilter <= 150
        // should match: ID 1 (100), ID 3 (50), ID 4 (150)
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::LTE,
                'value' => '150',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([1, 3, 4]);
    }

    public function testFilterBetween(): void
    {
        // filter for numericFilter BETWEEN 100 AND 200
        // should match: ID 1 (100), ID 2 (200), ID 4 (150), ID 5 (175)
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::BETWEEN,
                'value' => '100',
                'value2' => '200',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntityIds([1, 2, 4, 5]);
    }

    public function testFilterBetweenNarrowRange(): void
    {
        // filter for numericFilter BETWEEN 150 AND 180
        // should match: ID 4 (150), ID 5 (175)
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::BETWEEN,
                'value' => '150',
                'value2' => '180',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([4, 5]);
    }

    public function testFilterNoResults(): void
    {
        // filter for a value that doesn't exist
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '99999',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(0);
    }

    public function testFilterDecimalEquals(): void
    {
        // test decimal filter field
        // filter for decimalFilter = 10.5
        // should match: ID 1
        $url = $this->generateFilteredIndexUrl([
            'decimalFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '10.5',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntityIds([1]);
    }

    public function testFilterDecimalGreaterThan(): void
    {
        // filter for decimalFilter > 20
        // should match: ID 2 (25.75), ID 6 (30.0)
        $url = $this->generateFilteredIndexUrl([
            'decimalFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => '20',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([2, 6]);
    }
}
