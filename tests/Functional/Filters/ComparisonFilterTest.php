<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\FilterTestEntityFactory;

class ComparisonFilterTest extends FilterFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([10, 20, 30, 40, 50, 60] as $value) {
            $this->entities[$value] = FilterTestEntityFactory::createOne(['comparisonFilter' => $value]);
        }
    }

    public function testFilterEquals(): void
    {
        // filter for records where comparisonFilter = 30
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::EQ,
                'value' => 30,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(1);
        $this->assertVisibleEntities([30]);
    }

    public function testFilterNotEquals(): void
    {
        // filter for records where comparisonFilter != 30
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::NEQ,
                'value' => 30,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(5);
        $this->assertVisibleEntities([10, 20, 40, 50, 60]);
    }

    public function testFilterGreaterThan(): void
    {
        // filter for records where comparisonFilter > 40
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => 40,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities([50, 60]);
    }

    public function testFilterGreaterThanOrEqual(): void
    {
        // filter for records where comparisonFilter >= 40
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::GTE,
                'value' => 40,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities([40, 50, 60]);
    }

    public function testFilterLessThan(): void
    {
        // filter for records where comparisonFilter < 30
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::LT,
                'value' => 30,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(2);
        $this->assertVisibleEntities([10, 20]);
    }

    public function testFilterLessThanOrEqual(): void
    {
        // filter for records where comparisonFilter <= 30
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::LTE,
                'value' => 30,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(3);
        $this->assertVisibleEntities([10, 20, 30]);
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
        $url = $this->generateFilteredIndexUrl([
            'comparisonFilter' => [
                'comparison' => ComparisonType::GT,
                'value' => 0,
            ],
        ]);

        $this->client->request('GET', $url);

        $this->assertFilteredCount(6);
        $this->assertVisibleEntities([10, 20, 30, 40, 50, 60]);
    }

    public function testFilterBoundaryValueGreaterThan(): void
    {
        // 60 is the largest value created in setUp()
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
        // 10 is the smallest value created in setUp()
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
