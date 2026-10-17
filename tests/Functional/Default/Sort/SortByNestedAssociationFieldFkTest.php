<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Default\Sort;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\SortNestedAssociationByFkCrudController;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests an AssociationField whose property is a nested association path ending at an
 * association ('latestRelease.category') WITHOUT setSortProperty(): sorting via
 * ?sort[latestRelease.category] orders by the foreign key of the leaf association
 * (the category id), mirroring how single-level associations are sorted.
 *
 * From fixtures, categories are inserted in this order (so their ids are increasing):
 * "Major" (used by Alpha Project), "Minor" (Beta Project), "Major" (Gamma Project);
 * "Delta Project" has no release, so its category is NULL.
 */
class SortByNestedAssociationFieldFkTest extends AbstractCrudTestCase
{
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
    }

    /**
     * @param list<string> $expectedProjectNames
     */
    #[DataProvider('provideSortTests')]
    public function testSorting(array $query, array $expectedProjectNames): void
    {
        $this->client->request('GET', $this->generateIndexUrl().'?'.http_build_query($query));

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
        // SQLite sorts NULL values first in ASC order and last in DESC order

        yield 'sort by nested association ASC orders by category id' => [
            ['sort' => ['latestRelease.category' => 'ASC']],
            ['Delta Project', 'Alpha Project', 'Beta Project', 'Gamma Project'],
        ];

        yield 'sort by nested association DESC orders by category id' => [
            ['sort' => ['latestRelease.category' => 'DESC']],
            ['Gamma Project', 'Beta Project', 'Alpha Project', 'Delta Project'],
        ];
    }
}
