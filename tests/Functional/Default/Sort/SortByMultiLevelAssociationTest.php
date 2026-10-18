<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\SortMultiLevelAssocCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\DatabasePlatformTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting on multi-level association traversal.
 * The controller sorts by publisher.favoriteCategory.name (2-level association chain).
 */
class SortByMultiLevelAssociationTest extends AbstractCrudTestCase
{
    use BlogPostsWithPublisherCategoriesTrait;
    use DatabasePlatformTrait;

    protected function getControllerFqcn(): string
    {
        return SortMultiLevelAssocCrudController::class;
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

    public function testCustomSortOnAssociationCombinedWithNestedDefaultSort(): void
    {
        // ?sort[publisher]=ASC replaces neither defaultSort entry, so this sort and the
        // 'publisher.favoriteCategory.name' default sort traverse the same association; the
        // 'publisher' JOIN must be added only once or Doctrine fails with
        // "[Semantical Error] 'publisher' is already defined"
        $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query(['sort' => ['publisher' => 'ASC']]));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('tbody tr td[data-column="title"]');
    }

    public function testNestedColumnIsSortable(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('th[data-column="publisher.favoriteCategory.name"] > a', 'The nested property column must render a sort link');
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTitles
     */
    #[DataProvider('provideSortTests')]
    public function testSorting(array $query, array $expectedTitles): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }
        $this->client->request('GET', $url);
        $expectedTitles = self::sortNullsLikeTheDatabase($expectedTitles, ['Post Delta']);

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
        // NULL values are first in ASC order and last in DESC order (except on PostgreSQL);
        // the secondary sort by title makes ties deterministic

        yield 'default sort (category name DESC, title ASC)' => [
            [],
            ['Post Beta', 'Post Alpha', 'Post Gamma', 'Post Delta'],
        ];

        yield 'URL sort by nested property ASC' => [
            ['sort' => ['publisher.favoriteCategory.name' => 'ASC', 'title' => 'ASC']],
            ['Post Delta', 'Post Alpha', 'Post Gamma', 'Post Beta'],
        ];

        yield 'URL sort by nested property DESC' => [
            ['sort' => ['publisher.favoriteCategory.name' => 'DESC', 'title' => 'DESC']],
            ['Post Beta', 'Post Gamma', 'Post Alpha', 'Post Delta'],
        ];

        yield 'invalid nested sort property falls back to default sort' => [
            ['sort' => ['publisher.favoriteCategory.secretField' => 'ASC']],
            ['Post Beta', 'Post Alpha', 'Post Gamma', 'Post Delta'],
        ];
    }
}
