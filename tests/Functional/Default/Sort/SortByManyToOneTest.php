<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\BlogPostSortCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\BlogPostFactory;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting by ManyToOne relationship property.
 */
class SortByManyToOneTest extends AbstractCrudTestCase
{
    /**
     * @var array<string, string>
     */
    private array $publisherNameByTitle = [];

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

        // publishers are created in non-alphabetical order so sorting by their
        // name gives a different result than sorting by their id
        $gamma = UserFactory::createOne(['name' => 'User Gamma']);
        $alpha = UserFactory::createOne(['name' => 'User Alpha']);
        $beta = UserFactory::createOne(['name' => 'User Beta']);

        $rows = [
            'Post Zebra' => $alpha,
            'Post Apple' => $gamma,
            'Post Mango' => null,
            'Post Banana' => $beta,
        ];
        foreach ($rows as $title => $publisher) {
            BlogPostFactory::createOne(['title' => $title, 'publisher' => $publisher]);
            // null associations are rendered with the "Null" label
            $this->publisherNameByTitle[$title] = $publisher?->getName() ?? 'Null';
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
        $this->assertSelectorTextSame('th.header-for-field-association[data-column="publisher"] > a', 'Publisher');
        $this->assertSelectorExists('th.header-for-field-association[data-column="publisher"] span.icon svg');
        $this->assertSelectorCount(\count($this->publisherNameByTitle), 'tbody tr');

        foreach ($expectedTitles as $index => $title) {
            $row = $index + 1;
            $publisherName = $this->publisherNameByTitle[$title];
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="title"]', $title, sprintf('Expected "%s" in row %d', $title, $row));
            $this->assertSelectorTextSame('tbody tr:nth-child('.$row.') td[data-column="publisher"]', $publisherName, sprintf('Expected "%s" in row %d', $publisherName, $row));
        }
    }

    public static function sorting(): \Generator
    {
        // default sort is by ID ASC (configured in the CRUD controller)
        yield 'default sorting (by id ASC)' => [
            [],
            ['Post Zebra', 'Post Apple', 'Post Mango', 'Post Banana'],
        ];

        // SQLite sorts NULL values first in ASC order and last in DESC order
        yield 'ascending by publisher name' => [
            ['sort' => ['publisher' => 'ASC']],
            ['Post Mango', 'Post Zebra', 'Post Banana', 'Post Apple'],
        ];

        yield 'descending by publisher name' => [
            ['sort' => ['publisher' => 'DESC']],
            ['Post Apple', 'Post Banana', 'Post Zebra', 'Post Mango'],
        ];
    }
}
