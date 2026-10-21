<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Registry\AdminControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Sort\SortNestedAssociationCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\DatabasePlatformTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests an AssociationField whose property is a nested association path ending at an
 * association ('publisher.favoriteCategory') with setSortProperty('name'), so sorting via
 * ?sort[publisher.favoriteCategory] orders by the related category's name.
 */
class SortByNestedAssociationFieldTest extends AbstractCrudTestCase
{
    use BlogPostsWithPublisherCategoriesTrait;
    use DatabasePlatformTrait;

    protected function getControllerFqcn(): string
    {
        return SortNestedAssociationCrudController::class;
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

    public function testNestedAssociationColumnIsSortableByDefault(): void
    {
        // the controller never calls setSortable(): nested association paths ending at a
        // single-valued association are sortable by default, like single-level associations
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('th[data-column="publisher.favoriteCategory"] > a', 'The nested association column must render a sort link');
    }

    public function testNestedAssociationCellAutoLinksToLeafEntityCrudController(): void
    {
        // the field doesn't call setCrudController(): the cell must link to the CRUD
        // controller of the entity at the end of the path (Category)
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        $hrefs = $crawler
            ->filter('tbody td[data-column="publisher.favoriteCategory"] a')
            ->each(static fn ($node): string => $node->attr('href'));

        // DefaultApp has several CRUD controllers for Category; the link must use the
        // one that the registry associates with that entity
        $categoryCrudControllerFqcn = static::getContainer()->get(AdminControllerRegistry::class)->findCrudControllerByEntity(Category::class);
        self::assertNotNull($categoryCrudControllerFqcn);

        // rows are sorted by title, so they follow the order of the trait without "Post Delta"
        $expectedHrefs = [];
        foreach (array_keys(array_filter(self::PUBLISHER_CATEGORY_BY_BLOG_POST_TITLE)) as $title) {
            $blogPost = $this->entityManager->getRepository(BlogPost::class)->findOneBy(['title' => $title]);
            $expectedHrefs[] = $this->adminUrlGenerator
                ->setDashboard(DashboardController::class)
                ->setController($categoryCrudControllerFqcn)
                ->setAction(Action::DETAIL)
                ->setEntityId($blogPost->getPublisher()->getFavoriteCategory()->getId())
                ->generateUrl();
        }

        self::assertSame($expectedHrefs, $hrefs, 'The blog posts with a publisher must link the favorite category of the publisher');
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedTitles
     */
    #[DataProvider('provideSortTests')]
    public function testSorting(array $query, array $expectedTitles): void
    {
        $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query($query));
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
        // the secondary sort by title makes the "Category A" tie deterministic

        yield 'sort by nested association ASC orders by category name' => [
            ['sort' => ['publisher.favoriteCategory' => 'ASC', 'title' => 'ASC']],
            ['Post Delta', 'Post Alpha', 'Post Gamma', 'Post Beta'],
        ];

        yield 'sort by nested association DESC orders by category name' => [
            ['sort' => ['publisher.favoriteCategory' => 'DESC', 'title' => 'DESC']],
            ['Post Beta', 'Post Gamma', 'Post Alpha', 'Post Delta'],
        ];
    }
}
