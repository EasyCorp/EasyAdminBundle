<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchNestedEmbeddedCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\EntityFactory\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SearchTestAuthorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\SearchTestEntityFactory;
use PHPUnit\Framework\Attributes\DataProvider;

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

        SearchTestEntityFactory::createSequence([
            ['author' => SearchTestAuthorFactory::new(['address' => (new Address())->setStreet(10)->setCity('New York')])],
            ['author' => SearchTestAuthorFactory::new(['address' => (new Address())->setStreet(20)->setCity('London')])],
            ['author' => SearchTestAuthorFactory::new(['address' => (new Address())->setStreet(30)->setCity('New York')])],
            ['author' => SearchTestAuthorFactory::new(['address' => (new Address())->setStreet(40)->setCity('Paris')])],
            ['author' => null],
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
        yield 'search author.address.city New York' => [
            'New York',
            2, // two entities whose authors are different but live in the same city
        ];

        yield 'search author.address.city London' => [
            'London',
            1,
        ];

        yield 'search author.address.city Paris' => [
            'Paris',
            1,
        ];
    }
}
