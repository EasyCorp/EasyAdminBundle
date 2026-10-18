<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchMultiLevelAssocCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectReleaseCategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectReleaseFactory;
use PHPUnit\Framework\Attributes\DataProvider;

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

        // a release category can't be shared because ProjectRelease::$category is a one-to-one association
        ProjectFactory::createSequence([
            ['latestRelease' => ProjectReleaseFactory::new(['category' => ProjectReleaseCategoryFactory::new(['name' => 'Major'])])],
            ['latestRelease' => ProjectReleaseFactory::new(['category' => ProjectReleaseCategoryFactory::new(['name' => 'Minor'])])],
            ['latestRelease' => ProjectReleaseFactory::new(['category' => ProjectReleaseCategoryFactory::new(['name' => 'Major'])])],
            ['latestRelease' => null],
        ]);
    }

    #[DataProvider('provideSearchTests')]
    public function testSearch(string $query, int $expectedResultCount): void
    {
        $this->client->request('GET', $this->generateIndexUrl($query));
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideSearchTests(): iterable
    {
        yield 'search 2-level assoc Major' => [
            'Major',
            2,
        ];

        yield 'search 2-level assoc Minor' => [
            'Minor',
            1,
        ];

        yield 'search non-matching category' => [
            'Nonexistent',
            0, // No project has this category name
        ];
    }
}
