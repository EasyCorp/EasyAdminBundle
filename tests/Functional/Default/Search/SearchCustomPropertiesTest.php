<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\SearchCustomPropertiesCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for custom search properties configuration.
 * The controller is configured to search only in title and author.email fields
 * (not in content or slug).
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

        $johnSmith = UserFactory::createOne(['email' => 'john.smith@example.com']);
        $janeDoe = UserFactory::createOne(['email' => 'jane.doe@example.com']);
        $aliceJohnson = UserFactory::createOne(['email' => 'alice@company.org']);

        BlogPostFactory::createSequence([
            [
                'title' => 'Introduction to PHP Programming',
                'content' => 'PHP was created by Rasmus Lerdorf.',
                'slug' => 'secret-code-123',
                'author' => $johnSmith,
            ],
            ['title' => 'Advanced Symfony Framework Guide', 'author' => $janeDoe],
            ['title' => 'REST API Development', 'author' => $janeDoe],
            ['title' => 'JavaScript and PHP Integration', 'author' => $aliceJohnson],
            [
                'title' => 'Testing Best Practices',
                'content' => 'PHPUnit is the standard testing framework.',
                'author' => $aliceJohnson,
            ],
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
        // the CRUD controller only searches in title and author.email
        yield 'search by title field matches' => [
            'PHP',
            2, // "Introduction to PHP Programming" and "JavaScript and PHP Integration" ("PHPUnit" is only in the content field)
        ];

        yield 'search by content field yields no results (not in search fields)' => [
            'Rasmus Lerdorf',
            0,
        ];

        yield 'search by author email domain' => [
            '@example.com',
            3, // John Smith (1) + Jane Doe (2)
        ];

        yield 'search by title and author email together' => [
            'Symfony "@example.com"',
            1, // "Advanced Symfony Framework Guide" by Jane Doe (jane.doe@example.com)
        ];

        yield 'search by title only - Framework' => [
            'Framework',
            1, // only "Advanced Symfony Framework Guide" ("framework" is also in the content of "Testing Best Practices")
        ];

        yield 'search by specific author email' => [
            '"alice@company.org"',
            2, // Alice Johnson's posts
        ];

        yield 'search by title with author email matching' => [
            'API jane.doe',
            1, // "REST API Development" is authored by Jane Doe (jane.doe@example.com) - both terms match
        ];

        yield 'search by non-searchable field yields no results' => [
            'secret-code',
            0, // slug is not in search fields
        ];
    }
}
