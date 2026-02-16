<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchAnyTermsCrudController;

/**
 * Tests for the any-terms search mode (OR logic).
 * Any search term can match for a result to be returned.
 * This controller is configured to only search in author.name and author.email fields.
 */
class SearchAnyTermsTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchAnyTermsCrudController::class;
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
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    public static function provideSearchTests(): iterable
    {
        // the CRUD Controller is configured to only search in author.name and author.email.
        // from fixtures, we have 4 authors:
        // - John Smith (john.smith@example.com): 2 entities
        // - Jane Doe (jane.doe@example.com): 2 entities
        // - Alice Johnson (alice@company.org): 2 entities
        // - Bob Williams (bob.williams@test.net): 1 entity
        // - 1 entity has no author

        yield 'search by entity text field yields no results (not in search fields)' => [
            'PHP Programming',
            0,
        ];

        yield 'search by content field yields no results (not in search fields)' => [
            'scripting language',
            0,
        ];

        yield 'search by author email domain' => [
            '@example.com',
            4, // John Smith (2) + Jane Doe (2)
        ];

        yield 'quoted search by specific author email' => [
            '"john.smith@"',
            2, // Only John Smith's entities
        ];

        yield 'search by author name' => [
            'Smith',
            2, // John Smith's entities (only Smith matches "Smith", not Johnson)
        ];

        yield 'any terms search - multiple authors (OR logic)' => [
            'John Alice',
            4, // John Smith (2) + Alice Johnson (2)
        ];

        yield 'quoted search by full author email' => [
            '"alice@company.org"',
            2, // Alice Johnson's entities
        ];
    }
}
