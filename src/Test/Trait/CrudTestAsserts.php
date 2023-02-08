<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

trait CrudTestAsserts
{
    protected function assertIndexFullRecordCount(int $expectedIndexRecordCount): void
    {
        if (0 > $expectedIndexRecordCount) {
            throw new \InvalidArgumentException();
        }

        if (0 === $expectedIndexRecordCount) {
            static::assertSelectorTextSame('.no-results', 'No results found.');
        } else {
            static::assertSelectorTextSame('.list-pagination-counter strong', (string) $expectedIndexRecordCount);
        }
    }

    protected function assertIndexPageRecordCount(int $expectedIndexPageRecordCount): void
    {
        // TODO : to implement
    }

    protected function assertIndexPagesCount(int $expectedIndexPagesCount): void
    {
        // TODO : to implement
    }

    protected function assertActionExistsForEntity(string $action, string|int $entityId): void
    {
        // TODO : to implement
    }

    protected function assertNotActionExistsForEntity(string $action, string|int $entityId): void
    {
        // TODO : to implement
    }

    protected function assertGlobalActionExists(string $action): void
    {
        // TODO : to implement
    }

    protected function assertNotGlobalActionExists(string $action): void
    {
        // TODO : to implement
    }

    protected function assertColumnExists(string $columnName): void
    {
        // TODO : to implement
    }

    protected function assertNotColumnExists(string $columnName): void
    {
        // TODO : to implement
    }

    protected function assertColumnHeaderContains(string $columnName, string $columnHeaderValue): void
    {
        // TODO : to implement
    }

    protected function assertNotColumnHeaderContains(string $columnName, string $columnHeaderValue): void
    {
        // TODO : to implement
    }
}
