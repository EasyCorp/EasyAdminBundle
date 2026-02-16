<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchNestedEmbeddedCrudController;

/**
 * Tests for search on association + embedded properties (e.g. author.address.city).
 * This scenario is currently broken and requires the fix from PR #7427 / Issue #6854.
 */
class SearchNestedEmbeddedPropertyTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchNestedEmbeddedCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
    }

    /**
     * @dataProvider provideSearchTests
     */
    public function testSearch(string $query, int $expectedResultCount): void
    {
        $this->client->request('GET', $this->generateIndexUrl($query));
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    public static function provideSearchTests(): iterable
    {
        // From fixtures, the SearchTestEntity entities and their author addresses:
        // Author John Smith (address: New York) → entities 0 and 3 (2 entities)
        // Author Jane Doe (address: London)     → entities 1 and 5 (2 entities)
        // Author Alice Johnson (address: New York) → entities 2 and 7 (2 entities)
        // Author Bob Williams (address: Paris)    → entity 4 (1 entity)
        // Entity 6 has no author

        yield 'search author.address.city New York' => [
            'New York',
            4, // John Smith (2) + Alice Johnson (2)
        ];

        yield 'search author.address.city London' => [
            'London',
            2, // Jane Doe's entities
        ];

        yield 'search author.address.city Paris' => [
            'Paris',
            1, // Bob Williams' entity
        ];
    }
}
