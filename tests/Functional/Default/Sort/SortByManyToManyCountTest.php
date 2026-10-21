<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\BlogPostSortCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\CategoryFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by ManyToMany relationship count.
 */
class SortByManyToManyCountTest extends AbstractCrudTestCase
{
    /**
     * @var array<string, int>
     */
    private const CATEGORY_COUNT_BY_TITLE = [
        'Post Zebra' => 2,
        'Post Apple' => 0,
        'Post Mango' => 3,
        'Post Banana' => 1,
        // same count as 'Post Zebra' to check that ties are sorted by id in the same direction
        'Post Cherry' => 2,
    ];

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

        $categories = CategoryFactory::createMany(max(self::CATEGORY_COUNT_BY_TITLE));
        foreach (self::CATEGORY_COUNT_BY_TITLE as $title => $categoryCount) {
            BlogPostFactory::createOne([
                'title' => $title,
                'categories' => \array_slice($categories, 0, $categoryCount),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTitles
     */
    #[DataProvider('sorting')]
    public function testSorting(array $query, array $expectedTitles): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame('th.header-for-field-association[data-column="categories"] > a', 'Categories');
        $this->assertSelectorExists('th.header-for-field-association[data-column="categories"] span.icon svg');
        $this->assertSelectorCount(\count(self::CATEGORY_COUNT_BY_TITLE), 'tbody tr');

        foreach ($expectedTitles as $index => $title) {
            $row = $index + 1;
            $categoryCount = (string) self::CATEGORY_COUNT_BY_TITLE[$title];
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="title"]', $title, sprintf('Expected "%s" in row %d', $title, $row));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="categories"]', $categoryCount, sprintf('Expected "%s" in row %d', $categoryCount, $row));
        }
    }

    public static function sorting(): \Generator
    {
        yield 'default sorting (no sort applied)' => [
            [],
            ['Post Zebra', 'Post Apple', 'Post Mango', 'Post Banana', 'Post Cherry'],
        ];

        yield 'ascending by categories count' => [
            ['sort' => ['categories' => 'ASC']],
            ['Post Apple', 'Post Banana', 'Post Zebra', 'Post Cherry', 'Post Mango'],
        ];

        yield 'descending by categories count' => [
            ['sort' => ['categories' => 'DESC']],
            ['Post Mango', 'Post Cherry', 'Post Zebra', 'Post Banana', 'Post Apple'],
        ];
    }
}
