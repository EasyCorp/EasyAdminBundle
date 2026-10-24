<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Search\SearchAnyTermsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

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

        $johnSmith = UserFactory::createOne(['name' => 'John Smith', 'email' => 'john.smith@example.com']);
        $janeDoe = UserFactory::createOne(['name' => 'Jane Doe', 'email' => 'jane.doe@example.com']);
        $aliceJohnson = UserFactory::createOne(['name' => 'Alice Johnson', 'email' => 'alice@company.org']);

        BlogPostFactory::createSequence([
            ['author' => $johnSmith],
            ['author' => $johnSmith],
            [
                'title' => 'Introduction to PHP Programming',
                'content' => 'PHP is a popular scripting language.',
                'author' => $janeDoe,
            ],
            ['author' => $aliceJohnson],
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
        // the CRUD controller only searches in author.name and author.email
        yield 'search by title yields no results (not in search fields)' => [
            'PHP Programming',
            0,
        ];

        yield 'search by content field yields no results (not in search fields)' => [
            'scripting language',
            0,
        ];

        yield 'search by author email domain' => [
            '@example.com',
            3, // John Smith (2) + Jane Doe (1)
        ];

        yield 'quoted search by specific author email' => [
            '"john.smith@"',
            2, // only John Smith's posts
        ];

        yield 'search by author name' => [
            'Smith',
            2, // John Smith's posts (only Smith matches "Smith", not Johnson)
        ];

        yield 'any terms search - multiple authors (OR logic)' => [
            'John Alice',
            3, // John Smith (2) + Alice Johnson (1)
        ];

        yield 'quoted search by full author email' => [
            '"alice@company.org"',
            1, // Alice Johnson's post
        ];
    }
}
