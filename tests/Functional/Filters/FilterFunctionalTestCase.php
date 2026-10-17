<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterTestEntityCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterTestEntity;

/**
 * Base class for filter functional tests.
 *
 * Provides common functionality for testing filters in the index view.
 */
abstract class FilterFunctionalTestCase extends AbstractCrudTestCase
{
    /**
     * The entities created by each test, indexed by a key that describes their filtered value.
     *
     * @var array<int|string, FilterTestEntity>
     */
    protected array $entities = [];

    protected function getControllerFqcn(): string
    {
        return FilterTestEntityCrudController::class;
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
     * Generate URL with filter parameters.
     *
     * @param array<string, array<string, mixed>> $filters Array of filter configurations
     *                                                     Example: ['textFilter' => ['comparison' => 'like', 'value' => '%test%']]
     */
    protected function generateFilteredIndexUrl(array $filters): string
    {
        $options = [];
        foreach ($filters as $fieldName => $filterConfig) {
            foreach ($filterConfig as $key => $value) {
                $options["filters[{$fieldName}][{$key}]"] = $value;
            }
        }

        return $this->getCrudUrl('index', null, $options);
    }

    /**
     * Assert that only the expected number of entities appear after filtering.
     */
    protected function assertFilteredCount(int $expectedCount): void
    {
        static::assertIndexFullEntityCount($expectedCount);
    }

    /**
     * Get the IDs of all visible entities in the table.
     *
     * @return array<int, int>
     */
    protected function getVisibleEntityIds(): array
    {
        $crawler = $this->client->getCrawler();
        $ids = [];

        $cells = $crawler->filter('td[data-column="id"]');
        foreach ($cells as $cell) {
            $ids[] = (int) trim($cell->textContent);
        }

        return $ids;
    }

    /**
     * Assert that only the expected entity IDs are visible after filtering.
     *
     * @param array<int, int> $expectedIds
     */
    protected function assertVisibleEntityIds(array $expectedIds): void
    {
        $actualIds = $this->getVisibleEntityIds();
        sort($actualIds);
        sort($expectedIds);

        static::assertSame(
            $expectedIds,
            $actualIds,
            sprintf(
                'Expected entity IDs [%s] but got [%s]',
                implode(', ', $expectedIds),
                implode(', ', $actualIds)
            )
        );
    }

    /**
     * Assert that only the entities stored with the given keys in $this->entities are visible.
     *
     * @param list<int|string> $keys
     */
    protected function assertVisibleEntities(array $keys): void
    {
        $this->assertVisibleEntityIds(array_map(fn (int|string $key): int => $this->entities[$key]->getId(), $keys));
    }
}
