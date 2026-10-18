<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\SearchMultiLevelAssocCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for search on multi-level association traversal.
 * The controller is configured to search in publisher.favoriteCategory.name (2-level association chain).
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

        $categoryAlpha = CategoryFactory::createOne(['name' => 'Category Alpha']);
        $categoryBeta = CategoryFactory::createOne(['name' => 'Category Beta']);

        BlogPostFactory::createSequence([
            ['publisher' => UserFactory::new(['favoriteCategory' => $categoryAlpha])],
            ['publisher' => UserFactory::new(['favoriteCategory' => $categoryBeta])],
            ['publisher' => UserFactory::new(['favoriteCategory' => $categoryAlpha])],
            ['publisher' => UserFactory::new()],
            ['publisher' => null],
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
        yield 'search 2-level assoc Alpha' => [
            'Alpha',
            2,
        ];

        yield 'search 2-level assoc Beta' => [
            'Beta',
            1,
        ];

        yield 'search non-matching category' => [
            'Nonexistent',
            0, // no publisher has this favorite category
        ];
    }
}
