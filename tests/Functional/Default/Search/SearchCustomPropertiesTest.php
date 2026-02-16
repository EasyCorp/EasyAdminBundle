<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SearchCustomPropertiesCrudController;

/**
 * Tests for custom search properties configuration.
 * The controller is configured to search only in searchableTextField and author.email fields
 * (not in searchableContentField).
 */
class SearchCustomPropertiesTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return SearchCustomPropertiesCrudController::class;
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
        // the CRUD Controller is configured to search in searchableTextField and author.email only.
        // from fixtures, searchableTextField values are:
        // - "Introduction to PHP Programming"
        // - "Advanced Symfony Framework Guide"
        // - "Database Design Patterns"
        // - "JavaScript and PHP Integration"
        // - "Testing Best Practices"
        // - "REST API Development"
        // - "Doctrine ORM Mastery"
        // - "Security in Web Applications"

        yield 'search by title field matches' => [
            'PHP',
            2, // "Introduction to PHP Programming" and "JavaScript and PHP Integration"
        ];

        yield 'search by content field yields no results (not in search fields)' => [
            'Rasmus Lerdorf',
            0, // This text is in searchableContentField, not searchableTextField
        ];

        yield 'search by author email domain' => [
            '@example.com',
            4, // John Smith (2) + Jane Doe (2) entities
        ];

        yield 'search by title and author email together' => [
            'Symfony "@example.com"',
            1, // "Advanced Symfony Framework Guide" by Jane Doe (jane.doe@example.com)
        ];

        yield 'search by title only - Framework' => [
            'Framework',
            1, // Only "Advanced Symfony Framework Guide"
        ];

        yield 'search by specific author email' => [
            '"alice@company.org"',
            2, // Alice Johnson's entities
        ];

        yield 'search by title with author email matching' => [
            'API jane.doe',
            1, // "REST API Development" is authored by Jane Doe (jane.doe@example.com) - both terms match
        ];

        yield 'search by non-searchable field yields no results' => [
            'secret-code',
            0, // nonSearchableField is not in search fields
        ];
    }
}
