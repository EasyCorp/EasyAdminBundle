<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

trait CrudTestIndexAsserts
{
    use CrudTestSelectors;

    protected static function assertIndexFullEntityCount(int $expectedIndexFullEntityCount, ?string $message = null): void
    {
        if (0 > $expectedIndexFullEntityCount) {
            throw new \InvalidArgumentException();
        }

        if (0 === $expectedIndexFullEntityCount) {
            $message ??= 'There should be no results found in the index table';
            static::assertSelectorTextSame('.no-results', 'No results found.', $message);

            return;
        }

        $message ??= sprintf('There should be a total of %d results found in the index table', $expectedIndexFullEntityCount);
        static::assertSelectorNotExists('.no-results');
        static::assertSelectorTextSame('.list-pagination-counter strong', (string) $expectedIndexFullEntityCount, $message);
    }

    protected function assertIndexPageEntityCount(int $expectedIndexPageEntityCount, ?string $message = null): void
    {
        if (0 > $expectedIndexPageEntityCount) {
            throw new \InvalidArgumentException();
        }

        if (0 === $expectedIndexPageEntityCount) {
            $message ??= 'There should be no results found in the index table';
            static::assertSelectorExists('tr.no-results', $message);

            return;
        }

        $message ??= sprintf('There should be %d results found in the current index page', $expectedIndexPageEntityCount);
        static::assertSelectorNotExists('tr.no-results', );
        static::assertSelectorExists('tbody tr');

        $indexPageEntityRows = $this->client->getCrawler()->filter('tbody tr');
        static::assertEquals($expectedIndexPageEntityCount, $indexPageEntityRows->count(), $message);
    }

    protected function assertIndexPagesCount(int $expectedIndexPagesCount, ?string $message = null): void
    {
        if (0 >= $expectedIndexPagesCount) {
            throw new \InvalidArgumentException();
        }

        $message ??= sprintf('There should be a total of %d pages in the index page', $expectedIndexPagesCount);

        $pageItemsSelector = '.list-pagination-paginator ul.pagination li.page-item';
        static::assertSelectorExists($pageItemsSelector);

        $pageItems = $this->client->getCrawler()->filter($pageItemsSelector);
        $lastNumberedPageItem = $pageItems->slice($pageItems->count() - 2, 1);
        static::assertEquals((string) $expectedIndexPagesCount, $lastNumberedPageItem->filter('a')->text(), $message);
    }

    protected function assertIndexEntityActionExists(string $action, string|int $entityId, ?string $message = null): void
    {
        $message ??= sprintf('The action %s has not been found for entity id %s', $action, (string) $entityId);

        $entityRow = $this->client->getCrawler()->filter($this->getIndexEntityRowSelector($entityId));
        self::assertCount(1, $entityRow, sprintf('The entity %s is not existing in the table', (string) $entityId));

        $action = $entityRow->first()->filter($this->getActionSelector($action));
        self::assertCount(1, $action, $message);
    }

    protected function assertIndexEntityActionNotExists(string $action, string|int $entityId, ?string $message = null): void
    {
        $message ??= sprintf('The action %s has been found for entity id %s', $action, (string) $entityId);

        $entityRow = $this->client->getCrawler()->filter($this->getIndexEntityRowSelector($entityId));
        self::assertCount(1, $entityRow, sprintf('The entity %s is not existing in the table', (string) $entityId));

        $action = $entityRow->first()->filter($this->getActionSelector($action));
        self::assertCount(0, $action, $message);
    }

    protected function assertIndexEntityActionTextSame(string $action, string $actionDisplay, string|int $entityId, ?string $message = null): void
    {
        $this->assertIndexEntityActionExists($action, $entityId);

        $message ??= sprintf('The action %s is not labelled with the following text : %s', $action, $actionDisplay);
        self::assertSelectorTextSame($this->getIndexEntityActionSelector($action, $entityId), $actionDisplay, $message);
    }

    protected function assertIndexEntityActionNotTextSame(string $action, string $actionDisplay, string|int $entityId, ?string $message = null): void
    {
        $this->assertIndexEntityActionExists($action, $entityId);

        $message ??= sprintf('The action %s is labelled with the following text : %s', $action, $actionDisplay);
        self::assertSelectorTextNotContains($this->getIndexEntityActionSelector($action, $entityId), $actionDisplay, $message);
    }

    protected function assertGlobalActionExists(string $action, ?string $message = null): void
    {
        $message ??= sprintf('The global action %s does not exist', $action);
        self::assertSelectorExists($this->getGlobalActionSelector($action), $message);
    }

    protected function assertGlobalActionNotExists(string $action, ?string $message = null): void
    {
        $message ??= sprintf('The global action %s does exist', $action);
        self::assertSelectorNotExists($this->getGlobalActionSelector($action), $message);
    }

    protected function assertGlobalActionDisplays(string $action, string $actionDisplay, ?string $message = null): void
    {
        $message ??= sprintf('The global action %s does not display %s', $action, $actionDisplay);
        self::assertSelectorTextSame($this->getGlobalActionSelector($action), $actionDisplay, $message);
    }

    protected function assertGlobalActionNotDisplays(string $action, string $actionDisplay, ?string $message = null): void
    {
        $message ??= sprintf('The global action %s does display %s', $action, $actionDisplay);
        self::assertSelectorTextNotContains($this->getGlobalActionSelector($action), $actionDisplay, $message);
    }

    protected function assertIndexColumnExists(string $columnName, ?string $message = null): void
    {
        $message ??= sprintf('The column %s is not existing', $columnName);
        self::assertSelectorExists($this->getIndexHeaderColumnSelector($columnName), $message);
    }

    protected function assertIndexColumnNotExists(string $columnName, ?string $message = null): void
    {
        $message ??= sprintf('The column %s is existing', $columnName);
        self::assertSelectorNotExists($this->getIndexHeaderColumnSelector($columnName), $message);
    }

    protected function assertIndexColumnHeaderContains(string $columnName, string $columnHeaderValue, ?string $message = null): void
    {
        $message ??= sprintf('The column %s does not contain %s', $columnName, $columnHeaderValue);
        self::assertSelectorTextSame($this->getIndexHeaderColumnSelector($columnName), $columnHeaderValue, $message);
    }

    protected function assertIndexColumnHeaderNotContains(string $columnName, string $columnHeaderValue, ?string $message = null): void
    {
        $message ??= sprintf('The column %s contains %s', $columnName, $columnHeaderValue);
        self::assertSelectorTextNotContains($this->getIndexHeaderColumnSelector($columnName), $columnHeaderValue, $message);
    }
}
