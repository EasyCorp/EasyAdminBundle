<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Filters;

use EasyCorp\Bundle\EasyAdminBundle\Test\AbstractCrudTestCase;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Synthetic\FilterTestEntityCrudController;

/**
 * Base class for filter functional tests.
 *
 * Provides common functionality for testing filters in the index view.
 */
abstract class FilterFunctionalTestCase extends AbstractCrudTestCase
{
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

    protected function tearDown(): void
    {
        parent::tearDown();
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
}
