<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\MultiColumnSortCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\DatabasePlatformTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by multiple columns simultaneously.
 */
class SortByMultipleColumnsTest extends AbstractCrudTestCase
{
    use DatabasePlatformTrait;

    /**
     * @var array<string, BlogPost>
     */
    private array $blogPosts = [];

    protected function getControllerFqcn(): string
    {
        return MultiColumnSortCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();

        // views and title values repeat so that every secondary (and the tertiary
        // publishedAt) sort column decides the order of some rows
        $rows = [
            'banana10' => ['title' => 'Banana', 'views' => 10, 'publishedAt' => new \DateTimeImmutable('2024-01-01 10:00:00')],
            'apple20' => ['title' => 'Apple', 'views' => 20, 'publishedAt' => new \DateTimeImmutable('2024-01-01 10:00:00')],
            'apple10February' => ['title' => 'Apple', 'views' => 10, 'publishedAt' => new \DateTimeImmutable('2024-02-01 10:00:00')],
            'apple10Null' => ['title' => 'Apple', 'views' => 10, 'publishedAt' => null],
            'apple10March' => ['title' => 'Apple', 'views' => 10, 'publishedAt' => new \DateTimeImmutable('2024-03-01 10:00:00')],
            'cherry20' => ['title' => 'Cherry', 'views' => 20, 'publishedAt' => new \DateTimeImmutable('2024-01-01 10:00:00')],
        ];
        foreach ($rows as $key => $row) {
            $this->blogPosts[$key] = BlogPostFactory::createOne($row);
        }
    }

    /**
     * @param array<string, mixed>     $query
     * @param list<array{string, int}> $expectedRows pairs of title and views values
     */
    #[DataProvider('multiColumnSorting')]
    public function testSortingByMultipleColumns(array $query, array $expectedRows): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(\count($this->blogPosts), 'tbody tr');

        foreach ($expectedRows as $index => [$title, $views]) {
            $this->assertRowTitleAndViews($index + 1, $title, $views);
        }
    }

    public static function multiColumnSorting(): \Generator
    {
        yield 'default sorting (views ASC, title ASC from CRUD config)' => [
            [],
            [['Apple', 10], ['Apple', 10], ['Apple', 10], ['Banana', 10], ['Apple', 20], ['Cherry', 20]],
        ];

        yield 'primary ASC, secondary ASC' => [
            ['sort' => ['views' => 'ASC', 'title' => 'ASC']],
            [['Apple', 10], ['Apple', 10], ['Apple', 10], ['Banana', 10], ['Apple', 20], ['Cherry', 20]],
        ];

        yield 'primary ASC, secondary DESC' => [
            ['sort' => ['views' => 'ASC', 'title' => 'DESC']],
            [['Banana', 10], ['Apple', 10], ['Apple', 10], ['Apple', 10], ['Cherry', 20], ['Apple', 20]],
        ];

        yield 'primary DESC, secondary ASC' => [
            ['sort' => ['views' => 'DESC', 'title' => 'ASC']],
            [['Apple', 20], ['Cherry', 20], ['Apple', 10], ['Apple', 10], ['Apple', 10], ['Banana', 10]],
        ];

        yield 'primary DESC, secondary DESC' => [
            ['sort' => ['views' => 'DESC', 'title' => 'DESC']],
            [['Cherry', 20], ['Apple', 20], ['Banana', 10], ['Apple', 10], ['Apple', 10], ['Apple', 10]],
        ];

        yield 'title primary ASC, views secondary ASC' => [
            ['sort' => ['title' => 'ASC', 'views' => 'ASC']],
            [['Apple', 10], ['Apple', 10], ['Apple', 10], ['Apple', 20], ['Banana', 10], ['Cherry', 20]],
        ];

        yield 'title primary DESC, views secondary DESC' => [
            ['sort' => ['title' => 'DESC', 'views' => 'DESC']],
            [['Cherry', 20], ['Banana', 10], ['Apple', 20], ['Apple', 10], ['Apple', 10], ['Apple', 10]],
        ];
    }

    public function testSortingByThreeColumns(): void
    {
        // use IntlDateFormatter to match EasyAdmin's default datetime format (MEDIUM/MEDIUM)
        $dateFormatter = new \IntlDateFormatter('en', \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM);

        $url = $this->generateIndexUrl().'?'.http_build_query([
            'sort' => ['views' => 'ASC', 'title' => 'ASC', 'publishedAt' => 'DESC'],
        ]);
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(\count($this->blogPosts), 'tbody tr');

        // NULL values are last in DESC order, except on PostgreSQL, where they are first
        $expectedOrder = self::isPostgreSql()
            ? ['apple10Null', 'apple10March', 'apple10February', 'banana10', 'apple20', 'cherry20']
            : ['apple10March', 'apple10February', 'apple10Null', 'banana10', 'apple20', 'cherry20'];
        foreach ($expectedOrder as $index => $key) {
            $row = $index + 1;
            $blogPost = $this->blogPosts[$key];
            $this->assertRowTitleAndViews($row, $blogPost->getTitle(), $blogPost->getViews());

            // null values are rendered with the "Null" label
            $expectedDateTime = null !== $blogPost->getPublishedAt() ? (string) $dateFormatter->format($blogPost->getPublishedAt()) : 'Null';
            $this->assertSelectorTextSame(
                'tbody tr:nth-child('.$row.') td[data-column="publishedAt"]',
                $expectedDateTime,
                sprintf('Expected publishedAt "%s" in row %d', $expectedDateTime, $row)
            );
        }
    }

    private function assertRowTitleAndViews(int $row, ?string $title, int $views): void
    {
        $this->assertSelectorTextSame(
            'tbody tr:nth-child('.$row.') td[data-column="title"]',
            (string) $title,
            sprintf('Expected title "%s" in row %d', $title, $row)
        );
        $this->assertSelectorTextSame(
            'tbody tr:nth-child('.$row.') td[data-column="views"]',
            (string) $views,
            sprintf('Expected views "%s" in row %d', $views, $row)
        );
    }
}
