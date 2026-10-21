<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\SearchEmbeddedCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for search on root-level embedded properties.
 * The controller is configured to search in name and address.city fields.
 */
class SearchEmbeddedPropertyTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchEmbeddedCrudController::class;
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

        UserFactory::createSequence([
            ['name' => 'User 1', 'address' => (new Address())->setCity('London')],
            ['name' => 'User 2', 'address' => (new Address())->setCity('Paris')],
            ['name' => 'User 3', 'address' => (new Address())->setCity('London')],
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
        yield 'search by embedded city London' => [
            'London',
            2, // User 1 and User 3
        ];

        yield 'search by embedded city Paris' => [
            'Paris',
            1, // User 2
        ];

        yield 'search by name field' => [
            'User 1',
            1, // User 1
        ];

        yield 'search combining name + city' => [
            'User 1 London',
            1, // User 1 (all terms match: name has "User 1", city is "London")
        ];

        yield 'search by non-matching city' => [
            'Tokyo',
            0, // no user lives in Tokyo
        ];
    }
}
