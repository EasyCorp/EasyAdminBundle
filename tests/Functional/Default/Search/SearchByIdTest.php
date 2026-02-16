<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchByIdCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\SearchTestEntity;

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

    /**
     * @dataProvider provideSearchTests
     */
    public function testSearch(string $query, int $expectedResultCount): void
    {
        $this->client->request('GET', $this->generateIndexUrl($query));
        static::assertResponseIsSuccessful();
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    public static function provideSearchTests(): iterable
    {
        // the CRUD Controller is configured to only search in the 'id' field.
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
        // first, get a valid ID from an existing entity
        $entity = $this->entityManager->getRepository(SearchTestEntity::class)->findOneBy([]);
        $this->assertNotNull($entity, 'At least one SearchTestEntity should exist in fixtures');

        $this->client->request('GET', $this->generateIndexUrl((string) $entity->getId()));
        static::assertResponseIsSuccessful();
        static::assertIndexFullEntityCount(1);
    }

    public function testSearchByNonExistentId(): void
    {
        // search for an ID that doesn't exist
        $this->client->request('GET', $this->generateIndexUrl('99999'));
        static::assertResponseIsSuccessful();
        static::assertIndexFullEntityCount(0);
    }
}
