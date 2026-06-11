<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchMultiLevelAssocCrudController;

/**
 * Tests for search on multi-level association traversal.
 * The controller is configured to search in latestRelease.category.name (2-level association chain).
 */
class SearchMultiLevelAssociationTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchMultiLevelAssocCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        if (!class_exists(PostgreSQLPlatform::class)) {
            $this->markTestSkipped('Doctrine DBAL 3.x+ is required.');
        }

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
        // From fixtures, the Project entities and their release categories:
        // 1. "Alpha Project" → latestRelease=v1.0 → category.name="Major"
        // 2. "Beta Project"  → latestRelease=v1.1 → category.name="Minor"
        // 3. "Gamma Project" → latestRelease=v2.0 → category.name="Major"
        // 4. "Delta Project" → latestRelease=null  (no release)

        yield 'search 2-level assoc Major' => [
            'Major',
            2, // Alpha Project and Gamma Project
        ];

        yield 'search 2-level assoc Minor' => [
            'Minor',
            1, // Beta Project
        ];

        yield 'search non-matching category' => [
            'Nonexistent',
            0, // No project has this category name
        ];
    }
}
