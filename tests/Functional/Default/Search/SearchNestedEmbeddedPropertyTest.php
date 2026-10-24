<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\SearchNestedEmbeddedCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for search on association + embedded properties (e.g. author.address.city).
 * The search joins the author association and then filters by a column of its
 * embedded Address.
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

        BlogPostFactory::createSequence([
            ['author' => UserFactory::new(['address' => (new Address())->setCity('New York')])],
            ['author' => UserFactory::new(['address' => (new Address())->setCity('London')])],
            ['author' => UserFactory::new(['address' => (new Address())->setCity('New York')])],
            ['author' => UserFactory::new(['address' => (new Address())->setCity('Paris')])],
            // an author without address must not match any city
            ['author' => UserFactory::new()],
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
            2, // two posts whose authors are different but live in the same city
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
