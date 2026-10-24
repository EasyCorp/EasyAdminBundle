<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\UserSortCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by OneToMany relationship count.
 */
class SortByOneToManyCountTest extends AbstractCrudTestCase
{
    /**
     * @var array<string, int>
     */
    private const BLOG_POST_COUNT_BY_USER_NAME = [
        'User Zebra' => 2,
        'User Apple' => 0,
        'User Mango' => 3,
        'User Banana' => 1,
        // same count as 'User Zebra' to check that ties are sorted by id in the same direction
        'User Cherry' => 2,
    ];

    protected function getControllerFqcn(): string
    {
        return UserSortCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        foreach (self::BLOG_POST_COUNT_BY_USER_NAME as $name => $blogPostCount) {
            // created from the user side because, with ['author' => $user], Foundry < 2.11.1
            // leaves only the last blog post in the in-memory collection of the user
            UserFactory::createOne([
                'name' => $name,
                'blogPosts' => BlogPostFactory::new()->many($blogPostCount),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedNames
     */
    #[DataProvider('sorting')]
    public function testSorting(array $query, array $expectedNames): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-association[data-column="blogPosts"] > a', 'Blog Posts');
        $this->assertSelectorExists('th.header-for-field-association[data-column="blogPosts"] span.icon svg');
        $this->assertSelectorCount(\count(self::BLOG_POST_COUNT_BY_USER_NAME), 'tbody tr');

        foreach ($expectedNames as $index => $name) {
            $row = $index + 1;
            $blogPostCount = (string) self::BLOG_POST_COUNT_BY_USER_NAME[$name];
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="name"]', $name, sprintf('Expected "%s" in row %d', $name, $row));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="blogPosts"]', $blogPostCount, sprintf('Expected "%s" in row %d', $blogPostCount, $row));
        }
    }

    public static function sorting(): \Generator
    {
        yield 'default sorting (no sort applied)' => [
            [],
            ['User Zebra', 'User Apple', 'User Mango', 'User Banana', 'User Cherry'],
        ];

        yield 'ascending by blogPosts count' => [
            ['sort' => ['blogPosts' => 'ASC']],
            ['User Apple', 'User Banana', 'User Zebra', 'User Cherry', 'User Mango'],
        ];

        yield 'descending by blogPosts count' => [
            ['sort' => ['blogPosts' => 'DESC']],
            ['User Mango', 'User Cherry', 'User Zebra', 'User Banana', 'User Apple'],
        ];
    }
}
