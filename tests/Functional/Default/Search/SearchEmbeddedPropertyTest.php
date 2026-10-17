<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchEmbeddedCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Money;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\ProjectFactory;
use PHPUnit\Framework\Attributes\DataProvider;

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

        ProjectFactory::createSequence([
            ['name' => 'Alpha Project', 'price' => (new Money())->setAmount(1000)->setCurrency('USD')],
            ['name' => 'Beta Project', 'price' => (new Money())->setAmount(2000)->setCurrency('EUR')],
            ['name' => 'Gamma Project', 'price' => (new Money())->setAmount(3000)->setCurrency('USD')],
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
