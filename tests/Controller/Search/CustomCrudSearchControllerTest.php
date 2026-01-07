<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Controller\Search;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Controller\Search\CustomCrudSearchController;

class CustomCrudSearchControllerTest extends AbstractCrudTestCase
{
    protected function getControllerFqcn(): string
    {
        return CustomCrudSearchController::class;
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
        // the CRUD Controller associated to this test has configured the search
        // properties used by the search engine. That's why results are not the default ones
        $numOfPostsWrittenByEachAuthor = 4;
        $numOfPostsPublishedByEachUser = 2;

        yield 'search by blog post title and author or publisher email no results' => [
            '"Blog Post 10" "user4@"',
            0,
        ];

        yield 'search by blog post title and author or publisher email' => [
            'Blog Post "user4@"',
            $numOfPostsWrittenByEachAuthor + $numOfPostsPublishedByEachUser,
        ];

        yield 'search by author and publisher email' => [
            'user1 user2@',
            $numOfPostsPublishedByEachUser,
        ];

        yield 'search by author and publisher email no results' => [
            'user1 user3@',
            0,
        ];

        yield 'search by author or publisher email' => [
            'user4',
            $numOfPostsWrittenByEachAuthor + $numOfPostsPublishedByEachUser,
        ];
    }
}
