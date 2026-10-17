<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ProjectDomain\ProjectReleaseCategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortNestedAssociationCrudController;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests an AssociationField whose property is a nested association path ending at an
 * association ('latestRelease.category') with setSortProperty('name'), so sorting via
 * ?sort[latestRelease.category] orders by the related category's name.
 */
class SortByNestedAssociationFieldTest extends AbstractCrudTestCase
{
    use ProjectsWithReleaseCategoriesTrait;

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
        $this->createProjectsWithReleaseCategories();
    }

    public function testNestedAssociationColumnIsSortableByDefault(): void
    {
        // the controller never calls setSortable(): nested association paths ending at a
        // single-valued association are sortable by default, like single-level associations
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('th[data-column="latestRelease.category"] > a', 'The nested association column must render a sort link');
    }

    public function testNestedAssociationCellAutoLinksToLeafEntityCrudController(): void
    {
        // the field doesn't call setCrudController(): the cell must link to the CRUD
        // controller of the entity at the end of the path (ProjectReleaseCategory)
        $crawler = $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();

        $hrefs = $crawler
            ->filter('tbody td[data-column="latestRelease.category"] a')
            ->each(static fn ($node): string => $node->attr('href'));

        $projectsWithRelease = \count(array_filter(self::RELEASE_CATEGORY_BY_PROJECT_NAME));
        self::assertGreaterThan(0, $projectsWithRelease);
        self::assertCount($projectsWithRelease, $hrefs, 'The projects with a release must link their category');

        // depending on the URL format, the target controller appears as a query parameter
        // (crudControllerFqcn=...) or as the URL path of its pretty route
        foreach ($hrefs as $href) {
            self::assertTrue(
                str_contains($href, rawurlencode(ProjectReleaseCategoryCrudController::class)) || str_contains($href, 'project-release-category'),
                sprintf('Expected href "%s" to target the ProjectReleaseCategory CRUD controller', $href)
            );
        }
    }

    /**
     * @param array<string, mixed> $query
     * @param list<string>         $expectedProjectNames
     */
    #[DataProvider('provideSortTests')]
    public function testSorting(array $query, array $expectedProjectNames): void
    {
        $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query($query));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(\count($expectedProjectNames), 'tbody tr');
        foreach ($expectedProjectNames as $index => $expectedProjectName) {
            $row = $index + 1;
            $this->assertSelectorTextSame(
                sprintf('tbody tr:nth-child(%d) td[data-column="name"]', $row),
                $expectedProjectName,
                sprintf('Expected "%s" in row %d', $expectedProjectName, $row)
            );
        }
    }

    /**
     * @return iterable<string, array{array<string, mixed>, list<string>}>
     */
    public static function provideSortTests(): iterable
    {
        // SQLite sorts NULL values first in ASC order and last in DESC order;
        // the secondary sort by project name makes the "Major" tie deterministic

        yield 'sort by nested association ASC orders by category name' => [
            ['sort' => ['latestRelease.category' => 'ASC', 'name' => 'ASC']],
            ['Delta Project', 'Alpha Project', 'Gamma Project', 'Beta Project'],
        ];

        yield 'sort by nested association DESC orders by category name' => [
            ['sort' => ['latestRelease.category' => 'DESC', 'name' => 'DESC']],
            ['Beta Project', 'Gamma Project', 'Alpha Project', 'Delta Project'],
        ];
    }
}
