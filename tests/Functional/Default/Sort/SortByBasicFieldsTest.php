<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\BlogPostSortCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by basic fields (text, integer, datetime).
 */
class SortByBasicFieldsTest extends AbstractCrudTestCase
{
    /**
     * @var array<string, BlogPost>
     */
    private array $blogPosts = [];

    protected function getControllerFqcn(): string
    {
        return BlogPostSortCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        // the order by id, title, views and publishedAt is different for each column
        $rows = [
            ['title' => 'Post Zebra', 'views' => 100, 'publishedAt' => new \DateTimeImmutable('2024-01-15 10:00:00')],
            ['title' => 'Post Apple', 'views' => 50, 'publishedAt' => new \DateTimeImmutable('2024-03-20 14:30:00')],
            ['title' => 'Post Mango', 'views' => 75, 'publishedAt' => null],
            ['title' => 'Post Banana', 'views' => 200, 'publishedAt' => new \DateTimeImmutable('2024-02-10 08:15:00')],
        ];
        foreach ($rows as $row) {
            $this->blogPosts[$row['title']] = BlogPostFactory::createOne($row);
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTitles
     */
    #[DataProvider('titleSorting')]
    public function testSortingByTitle(array $query, array $expectedTitles): void
    {
        $this->requestIndex($query);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-text[data-column="title"] > a', 'Title');
        $this->assertRowsInOrder($expectedTitles, 'title', static fn (BlogPost $blogPost): string => $blogPost->getTitle());
    }

    public static function titleSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            ['Post Zebra', 'Post Apple', 'Post Mango', 'Post Banana'],
        ];

        yield 'ascending by title' => [
            ['sort' => ['title' => 'ASC']],
            ['Post Apple', 'Post Banana', 'Post Mango', 'Post Zebra'],
        ];

        yield 'descending by title' => [
            ['sort' => ['title' => 'DESC']],
            ['Post Zebra', 'Post Mango', 'Post Banana', 'Post Apple'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTitles
     */
    #[DataProvider('viewsSorting')]
    public function testSortingByViews(array $query, array $expectedTitles): void
    {
        $this->requestIndex($query);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-integer[data-column="views"] > a', 'Views');
        $this->assertRowsInOrder($expectedTitles, 'views', static fn (BlogPost $blogPost): string => (string) $blogPost->getViews());
    }

    public static function viewsSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            ['Post Zebra', 'Post Apple', 'Post Mango', 'Post Banana'],
        ];

        yield 'ascending by views' => [
            ['sort' => ['views' => 'ASC']],
            ['Post Apple', 'Post Mango', 'Post Zebra', 'Post Banana'],
        ];

        yield 'descending by views' => [
            ['sort' => ['views' => 'DESC']],
            ['Post Banana', 'Post Zebra', 'Post Mango', 'Post Apple'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTitles
     */
    #[DataProvider('publishedAtSorting')]
    public function testSortingByPublishedAt(array $query, array $expectedTitles): void
    {
        // use IntlDateFormatter to match EasyAdmin's default datetime format (MEDIUM/MEDIUM)
        $dateFormatter = new \IntlDateFormatter('en', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM);

        $this->requestIndex($query);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-datetime[data-column="publishedAt"] > a', 'Published At');
        // null values are rendered with the "Null" label
        $this->assertRowsInOrder($expectedTitles, 'publishedAt', static fn (BlogPost $blogPost): string => null !== $blogPost->getPublishedAt() ? (string) $dateFormatter->format($blogPost->getPublishedAt()) : 'Null');
    }

    public static function publishedAtSorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            ['Post Zebra', 'Post Apple', 'Post Mango', 'Post Banana'],
        ];

        // SQLite sorts NULL values first in ASC order and last in DESC order
        yield 'ascending by publishedAt' => [
            ['sort' => ['publishedAt' => 'ASC']],
            ['Post Mango', 'Post Zebra', 'Post Banana', 'Post Apple'],
        ];

        yield 'descending by publishedAt' => [
            ['sort' => ['publishedAt' => 'DESC']],
            ['Post Apple', 'Post Banana', 'Post Zebra', 'Post Mango'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    private function requestIndex(array $query): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }

        $this->client->request('GET', $url);
    }

    /**
     * @param list<string>               $expectedTitles
     * @param \Closure(BlogPost): string $expectedColumnValue
     */
    private function assertRowsInOrder(array $expectedTitles, string $column, \Closure $expectedColumnValue): void
    {
        $this->assertSelectorCount(\count($this->blogPosts), 'tbody tr');

        foreach ($expectedTitles as $index => $title) {
            $row = $index + 1;
            $expectedValue = $expectedColumnValue($this->blogPosts[$title]);
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="title"]', $title, sprintf('Expected "%s" in row %d', $title, $row));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="'.$column.'"]', $expectedValue, sprintf('Expected "%s" in row %d', $expectedValue, $row));
        }
    }
}
