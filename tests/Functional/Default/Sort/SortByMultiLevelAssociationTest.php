<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortMultiLevelAssocCrudController;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests sorting on multi-level association traversal.
 * The controller sorts by latestRelease.category.name (2-level association chain).
 *
 * From fixtures, the Project entities and their release categories:
 * 1. "Alpha Project" → latestRelease=v1.0 → category.name="Major"
 * 2. "Beta Project"  → latestRelease=v1.1 → category.name="Minor"
 * 3. "Gamma Project" → latestRelease=v2.0 → category.name="Major"
 * 4. "Delta Project" → latestRelease=null  (no release)
 */
class SortByMultiLevelAssociationTest extends AbstractCrudTestCase
{
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
    }

    public function testCustomSortOnAssociationCombinedWithNestedDefaultSort(): void
    {
        // ?sort[latestRelease]=ASC replaces neither defaultSort entry, so this sort and the
        // 'latestRelease.category.name' default sort traverse the same association; the
        // 'latestRelease' JOIN must be added only once or Doctrine fails with
        // "[Semantical Error] 'latestRelease' is already defined"
        $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query(['sort' => ['latestRelease' => 'ASC']]));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('tbody tr td[data-column="name"]');
    }

    public function testNestedColumnIsSortable(): void
    {
        $this->client->request('GET', $this->generateIndexUrl());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('th[data-column="latestRelease.category.name"] > a', 'The nested property column must render a sort link');
    }

    /**
     * @param list<string> $expectedProjectNames
     */
    #[DataProvider('provideSortTests')]
    public function testSorting(array $query, array $expectedProjectNames): void
    {
        $url = $this->generateIndexUrl();
        if ([] !== $query) {
            $url .= '?'.http_build_query($query);
        }
        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        foreach ($expectedProjectNames as $index => $expectedProjectName) {
            $row = $index + 1;
            $this->assertSelectorTextSame(
                sprintf('tbody tr:nth-child(%d) td[data-column="name"]', $row),
                $expectedProjectName,
                sprintf('Expected "%s" in row %d', $expectedProjectName, $row)
            );
        }
    }

    public static function provideSortTests(): iterable
    {
        // SQLite sorts NULL values first in ASC order and last in DESC order;
        // the secondary sort by project name makes ties deterministic

        yield 'default sort (category name DESC, name ASC)' => [
            [],
            ['Beta Project', 'Alpha Project', 'Gamma Project', 'Delta Project'],
        ];

        yield 'URL sort by nested property ASC' => [
            ['sort' => ['latestRelease.category.name' => 'ASC', 'name' => 'ASC']],
            ['Delta Project', 'Alpha Project', 'Gamma Project', 'Beta Project'],
        ];

        yield 'URL sort by nested property DESC' => [
            ['sort' => ['latestRelease.category.name' => 'DESC', 'name' => 'DESC']],
            ['Beta Project', 'Gamma Project', 'Alpha Project', 'Delta Project'],
        ];

        yield 'invalid nested sort property falls back to default sort' => [
            ['sort' => ['latestRelease.category.secretField' => 'ASC']],
            ['Beta Project', 'Alpha Project', 'Gamma Project', 'Delta Project'],
        ];
    }
}
