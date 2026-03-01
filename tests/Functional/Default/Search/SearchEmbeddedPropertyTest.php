<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchEmbeddedCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;

/**
 * Tests for search on root-level embedded properties.
 * The controller is configured to search in name and price.currency fields.
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

        // Remove any Project entities created by other test classes to ensure consistent fixture state
        $this->entityManager->createQuery(
            'DELETE FROM '.Project::class.' p WHERE p.name NOT IN (:names)'
        )
        ->setParameter('names', ['Alpha Project', 'Beta Project', 'Gamma Project', 'Delta Project'])
        ->execute();
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
        // From fixtures, the Project entities are:
        // 1. "Alpha Project", price=(1000, "USD")
        // 2. "Beta Project",  price=(2000, "EUR")
        // 3. "Gamma Project", price=(3000, "USD")
        // 4. "Delta Project", price=(500,  "GBP")

        yield 'search by embedded currency USD' => [
            'USD',
            2, // Alpha Project and Gamma Project
        ];

        yield 'search by embedded currency EUR' => [
            'EUR',
            1, // Beta Project
        ];

        yield 'search by name field' => [
            'Alpha',
            1, // Alpha Project
        ];

        yield 'search combining name + currency' => [
            'Alpha USD',
            1, // Alpha Project (both terms match: name has "Alpha", currency is "USD")
        ];

        yield 'search by non-matching currency' => [
            'JPY',
            0, // No project has JPY currency
        ];
    }
}
