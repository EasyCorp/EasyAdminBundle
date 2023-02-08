<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

/**
 * TODO: list of the needed assertions
 * - assert if an action exists/not exists
 * - assert if a global action exists/not exists
 * - assert if record data exists/not exists
 * - assert if record data equals/contains a specific value.
 */
trait CrudTestAsserts
{
    protected function assertIndexRecordCount(int $expectedIndexRecordCount): void
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
}
