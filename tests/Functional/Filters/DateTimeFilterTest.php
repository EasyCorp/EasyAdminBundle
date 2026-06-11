<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

/**
 * Fixture data (dateFilter field):
 * - ID 1: 2024-01-15
 * - ID 2: 2024-02-20
 * - ID 3: 2024-03-10
 * - ID 4: 2024-01-25
 * - ID 5: 2024-02-01
 * - ID 6: 2024-04-01
 */
class DateTimeFilterTest extends FilterFunctionalTestCase
{
    public function testFilterEquals(): void
    {
        // filter for dateFilter = 2024-01-15
        // should match: ID 1
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '2024-01-15',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntityIds([1]);
    }

    public function testFilterNotEquals(): void
    {
        // filter for dateFilter != 2024-01-15
        // should match all except ID 1
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => '2024-01-15',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntityIds([2, 3, 4, 5, 6]);
    }

    public function testFilterAfter(): void
    {
        // filter for dateFilter > 2024-02-01
        // should match: ID 2 (2024-02-20), ID 3 (2024-03-10), ID 6 (2024-04-01)
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => '2024-02-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([2, 3, 6]);
    }

    public function testFilterAfterOrSame(): void
    {
        // filter for dateFilter >= 2024-02-01
        // should match: ID 2 (2024-02-20), ID 3 (2024-03-10), ID 5 (2024-02-01), ID 6 (2024-04-01)
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::GTE,
                'value' => '2024-02-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntityIds([2, 3, 5, 6]);
    }

    public function testFilterBefore(): void
    {
        // filter for dateFilter < 2024-02-01
        // should match: ID 1 (2024-01-15), ID 4 (2024-01-25)
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::LT,
                'value' => '2024-02-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([1, 4]);
    }

    public function testFilterBeforeOrSame(): void
    {
        // filter for dateFilter <= 2024-02-01
        // should match: ID 1 (2024-01-15), ID 4 (2024-01-25), ID 5 (2024-02-01)
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::LTE,
                'value' => '2024-02-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([1, 4, 5]);
    }

    public function testFilterBetween(): void
    {
        // filter for dateFilter BETWEEN 2024-01-20 AND 2024-03-01
        // should match: ID 2 (2024-02-20), ID 4 (2024-01-25), ID 5 (2024-02-01)
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::BETWEEN,
                'value' => '2024-01-20',
                'value2' => '2024-03-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([2, 4, 5]);
    }

    public function testFilterBetweenJanuaryOnly(): void
    {
        // filter for dates in January 2024
        // should match: ID 1 (2024-01-15), ID 4 (2024-01-25)
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::BETWEEN,
                'value' => '2024-01-01',
                'value2' => '2024-01-31',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntityIds([1, 4]);
    }

    public function testFilterNoResults(): void
    {
        // filter for a date in the past that doesn't exist
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '2020-01-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(0);
    }

    public function testDateTimeFilterAfter(): void
    {
        // test dateTimeFilter field (includes time)
        // filter for dateTimeFilter > 2024-02-01 12:00:00
        // should match: ID 2 (2024-02-20 14:00), ID 3 (2024-03-10 08:15), ID 6 (2024-04-01 09:00)
        $url = $this->generateFilteredIndexUrl([
            'dateTimeFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => '2024-02-01 12:00:00',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntityIds([2, 3, 6]);
    }
}
