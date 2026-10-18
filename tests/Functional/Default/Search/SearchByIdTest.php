<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\SearchByIdCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use function Zenstruck\Foundry\Persistence\delete;

/**
 * Tests for searching by ID only.
 * The controller is configured to only search in the 'id' field.
 */
class SearchByIdTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchByIdCrudController::class;
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

    #[DataProvider('provideSearchTests')]
    public function testSearch(string $query, int $expectedResultCount): void
    {
        BlogPostFactory::createOne([
            'title' => 'Introduction to PHP Programming',
            'content' => 'Symfony is a set of reusable PHP components.',
            'author' => UserFactory::new(['name' => 'John Smith']),
        ]);

        $this->client->request('GET', $this->generateIndexUrl($query));
        static::assertResponseIsSuccessful();
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function provideSearchTests(): iterable
    {
        // the CRUD controller only searches in the 'id' field
        yield 'search by non numeric query yields no results' => [
            'PHP Programming',
            0,
        ];

        yield 'search by text content yields no results' => [
            'Symfony',
            0,
        ];

        yield 'search by author name yields no results' => [
            'John Smith',
            0,
        ];
    }

    public function testSearchByValidId(): void
    {
        $entities = BlogPostFactory::createMany(2);

        $this->client->request('GET', $this->generateIndexUrl((string) $entities[1]->getId()));
        static::assertResponseIsSuccessful();
        static::assertIndexFullEntityCount(1);
        $this->assertSelectorExists(sprintf('tbody tr[data-id="%d"]', $entities[1]->getId()));
    }

    public function testSearchByNonExistentId(): void
    {
        [$deletedEntity] = BlogPostFactory::createMany(2);
        $deletedEntityId = $deletedEntity->getId();
        delete($deletedEntity);

        $this->client->request('GET', $this->generateIndexUrl((string) $deletedEntityId));
        static::assertResponseIsSuccessful();
        static::assertIndexFullEntityCount(0);
    }
}
