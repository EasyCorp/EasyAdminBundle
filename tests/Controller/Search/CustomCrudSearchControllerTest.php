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
    public function testSearch(string $query, int $expectedResultCount)
    {
        $this->client->request('GET', $this->generateIndexUrl($query));
        static::assertIndexFullEntityCount($expectedResultCount);
    }

    public static function provideSearchTests(): iterable
    {
        // the CRUD Controller associated to this test has configured the search
        // properties used by the search engine. That's why results are not the default ones
        $totalNumberOfPosts = 20;
        $numOfPostsWrittenByEachAuthor = 4;
        $numOfPostsPublishedByEachUser = 2;

        yield 'search by blog post title yields no results' => [
            'blog post',
            0,
        ];

        yield 'search by blog post slug yields no results' => [
            'blog-post',
            0,
        ];

        yield 'search by author or publisher email' => [
            '@example.com',
            $totalNumberOfPosts,
        ];

        yield 'quoted search by author or published email' => [
            '"user4@"',
            $numOfPostsWrittenByEachAuthor + $numOfPostsPublishedByEachUser,
        ];

        yield 'multiple search by author or publisher email (partial or complete)' => [
            '"user2@example.com" "user4@"',
            2 * $numOfPostsWrittenByEachAuthor + 2 * $numOfPostsPublishedByEachUser,
        ];
    }
}
