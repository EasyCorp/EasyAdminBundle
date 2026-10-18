<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;

class NumericFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $decimals = [100 => 10.5, 200 => 25.75, 50 => 5.25, 150 => 15.0, 175 => 17.5, 300 => 30.0];
        foreach ($decimals as $number => $decimal) {
            $this->entities[$number] = FilterTestEntityFactory::createOne([
                'numericFilter' => $number,
                'decimalFilter' => $decimal,
            ]);
        }
    }

    public function testFilterEquals(): void
    {
        // filter for numericFilter = 100
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '100',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities([100]);
    }

    public function testFilterNotEquals(): void
    {
        // filter for numericFilter != 100
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => '100',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntities([200, 50, 150, 175, 300]);
    }

    public function testFilterGreaterThan(): void
    {
        // filter for numericFilter > 150
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => '150',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities([200, 175, 300]);
    }

    public function testFilterGreaterThanOrEqual(): void
    {
        // filter for numericFilter >= 150
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::GTE,
                'value' => '150',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntities([200, 150, 175, 300]);
    }

    public function testFilterLessThan(): void
    {
        // filter for numericFilter < 150
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::LT,
                'value' => '150',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities([100, 50]);
    }

    public function testFilterLessThanOrEqual(): void
    {
        // filter for numericFilter <= 150
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::LTE,
                'value' => '150',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities([100, 50, 150]);
    }

    public function testFilterBetween(): void
    {
        // filter for numericFilter BETWEEN 100 AND 200
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::BETWEEN,
                'value' => '100',
                'value2' => '200',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(4);
        $this->assertVisibleEntities([100, 200, 150, 175]);
    }

    public function testFilterBetweenNarrowRange(): void
    {
        // filter for numericFilter BETWEEN 150 AND 180
        $url = $this->generateFilteredIndexUrl([
            'numericFilter' => [
                'comparison' => ComparisonType::BETWEEN,
                'value' => '150',
                'value2' => '180',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities([150, 175]);
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
        $url = $this->generateFilteredIndexUrl([
            'decimalFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => '10.5',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities([100]);
    }

    public function testFilterDecimalGreaterThan(): void
    {
        // filter for decimalFilter > 20
        $url = $this->generateFilteredIndexUrl([
            'decimalFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => '20',
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities([200, 300]);
    }
}
