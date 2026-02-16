<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

/**
 * Fixture data (comparisonFilter field):
 * - ID 1: 10
 * - ID 2: 20
 * - ID 3: 30
 * - ID 4: 40
 * - ID 5: 50
 * - ID 6: 60
 */
class ComparisonFilterTest extends FilterFunctionalTestCase
{
    public function testFilterEquals(): void
    {
        // filter for records where comparisonFilter = 30
        // should match: ID 3
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => 30,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntityIds([3]);
    }

    public function testFilterNotEquals(): void
    {
        // filter for records where comparisonFilter != 30
        // should match: IDs 1, 2, 4, 5, 6
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => 30,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntityIds([1, 2, 4, 5, 6]);
    }

    public function testFilterGreaterThan(): void
    {
        // filter for records where comparisonFilter > 40
        // should match: IDs 5 (50), 6 (60)
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => 40,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([5, 6]);
    }

    public function testFilterGreaterThanOrEqual(): void
    {
        // filter for records where comparisonFilter >= 40
        // should match: IDs 4 (40), 5 (50), 6 (60)
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::GTE,
                'value' => 40,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([4, 5, 6]);
    }

    public function testFilterLessThan(): void
    {
        // filter for records where comparisonFilter < 30
        // should match: IDs 1 (10), 2 (20)
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::LT,
                'value' => 30,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([1, 2]);
    }

    public function testFilterLessThanOrEqual(): void
    {
        // filter for records where comparisonFilter <= 30
        // should match: IDs 1 (10), 2 (20), 3 (30)
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::LTE,
                'value' => 30,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([1, 2, 3]);
    }

    public function testFilterNoResults(): void
    {
        // filter for records where comparisonFilter = 999 (doesn't exist)
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => 999,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(0);
    }

    public function testFilterAllResults(): void
    {
        // filter for records where comparisonFilter > 0
        // should match all 6 records
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => 0,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(6);
        $this->assertVisibleEntityIds([1, 2, 3, 4, 5, 6]);
    }

    public function testFilterBoundaryValueGreaterThan(): void
    {
        // filter for records where comparisonFilter > 60
        // should match: none (60 is the maximum)
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => 60,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(0);
    }

    public function testFilterBoundaryValueLessThan(): void
    {
        // filter for records where comparisonFilter < 10
        // should match: none (10 is the minimum)
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::LT,
                'value' => 10,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(0);
    }
}
