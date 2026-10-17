<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;

class DateTimeFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $dateTimes = [
            '2024-01-15' => '2024-01-15 10:30:00',
            '2024-02-20' => '2024-02-20 14:00:00',
            '2024-03-10' => '2024-03-10 08:15:00',
            '2024-01-25' => '2024-01-25 16:45:00',
            '2024-02-01' => '2024-02-01 12:00:00',
            '2024-04-01' => '2024-04-01 09:00:00',
        ];
        foreach ($dateTimes as $date => $dateTime) {
            $this->entities[$date] = FilterTestEntityFactory::createOne([
                'dateFilter' => new \DateTime($date),
                'dateTimeFilter' => new \DateTime($dateTime),
            ]);
        }
    }

    public function testFilterEquals(): void
    {
        // filter for dateFilter = 2024-01-15
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '2024-01-15',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities(['2024-01-15']);
    }

    public function testFilterNotEquals(): void
    {
        // filter for dateFilter != 2024-01-15
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => '2024-01-15',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntities(['2024-02-20', '2024-03-10', '2024-01-25', '2024-02-01', '2024-04-01']);
    }

    public function testFilterAfter(): void
    {
        // filter for dateFilter > 2024-02-01
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => '2024-02-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities(['2024-02-20', '2024-03-10', '2024-04-01']);
    }

    public function testFilterAfterOrSame(): void
    {
        // filter for dateFilter >= 2024-02-01
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::GTE,
                'value' => '2024-02-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntities(['2024-02-20', '2024-03-10', '2024-02-01', '2024-04-01']);
    }

    public function testFilterBefore(): void
    {
        // filter for dateFilter < 2024-02-01
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::LT,
                'value' => '2024-02-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['2024-01-15', '2024-01-25']);
    }

    public function testFilterBeforeOrSame(): void
    {
        // filter for dateFilter <= 2024-02-01
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::LTE,
                'value' => '2024-02-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities(['2024-01-15', '2024-01-25', '2024-02-01']);
    }

    public function testFilterBetween(): void
    {
        // filter for dateFilter BETWEEN 2024-01-20 AND 2024-03-01
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::BETWEEN,
                'value' => '2024-01-20',
                'value2' => '2024-03-01',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities(['2024-02-20', '2024-01-25', '2024-02-01']);
    }

    public function testFilterBetweenJanuaryOnly(): void
    {
        // filter for dates in January 2024
        $url = $this->generateFilteredIndexUrl([
            'dateFilter' => [
                'comparison' => ComparisonType::BETWEEN,
                'value' => '2024-01-01',
                'value2' => '2024-01-31',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities(['2024-01-15', '2024-01-25']);
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
        $url = $this->generateFilteredIndexUrl([
            'dateTimeFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => '2024-02-01 12:00:00',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities(['2024-02-20', '2024-03-10', '2024-04-01']);
    }
}
