<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\SortNestedAssociationByFkCrudController;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests an AssociationField whose property is a nested association path ending at an
 * association ('publisher.favoriteCategory') WITHOUT setSortProperty(): sorting via
 * ?sort[publisher.favoriteCategory] orders by the foreign key of the leaf association
 * (the category id), mirroring how single-level associations are sorted.
 */
class SortByNestedAssociationFieldFkTest extends AbstractCrudTestCase
{
    use BlogPostsWithPublisherCategoriesTrait;

    protected function getControllerFqcn(): string
    {
        return SortNestedAssociationByFkCrudController::class;
    }

    protected function getDashboardFqcn(): string
    {
        return DashboardController::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->followRedirects();
        $this->createBlogPostsWithPublisherCategories();
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTitles
     */
    #[DataProvider('provideSortTests')]
    public function testSorting(array $query, array $expectedTitles): void
    {
        $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query($query));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(\count($expectedTitles), 'tbody tr');
        foreach ($expectedTitles as $index => $expectedTitle) {
            $row = $index + 1;
            $this->assertSelectorTextSame(
                sprintf('tbody tr:nth-child(%d) td[data-column="title"]', $row),
                $expectedTitle,
                sprintf('Expected "%s" in row %d', $expectedTitle, $row)
            );
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function provideSortTests(): iterable
    {
        // SQLite sorts NULL values first in ASC order and last in DESC order

        yield 'sort by nested association ASC orders by category id' => [
            ['sort' => ['publisher.favoriteCategory' => 'ASC']],
            ['Post Delta', 'Post Alpha', 'Post Beta', 'Post Gamma'],
        ];

        yield 'sort by nested association DESC orders by category id' => [
            ['sort' => ['publisher.favoriteCategory' => 'DESC']],
            ['Post Gamma', 'Post Beta', 'Post Alpha', 'Post Delta'],
        ];
    }
}
