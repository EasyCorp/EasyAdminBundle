<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

/**
 * Helpers for the assertions whose result depends on the database server running the
 * tests (see EASYADMIN_TEST_DATABASE_URL in tests/AGENTS.md).
 */
trait DatabasePlatformTrait
{
    private static function isPostgreSql(): bool
    {
        return self::getContainer()->get('doctrine')->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform;
    }

    /**
     * Sort tests list their expected rows as SQLite, MySQL and MariaDB sort them: NULL is
     * lower than any other value. PostgreSQL considers NULL higher than any other value,
     * so the rows whose sort value is NULL move from one end of the list to the other.
     *
     * @template T
     *
     * @param list<T> $expectedRows
     * @param list<T> $rowsWithNull
     *
     * @return list<T>
     */
    private static function sortNullsLikeTheDatabase(array $expectedRows, array $rowsWithNull): array
    {
        if (!self::isPostgreSql()) {
            return $expectedRows;
        }

        $nullCount = \count($rowsWithNull);
        $rowsWithValue = array_values(array_filter($expectedRows, static fn (mixed $row): bool => !\in_array($row, $rowsWithNull, true)));
        $nullRows = array_values(array_filter($expectedRows, static fn (mixed $row): bool => \in_array($row, $rowsWithNull, true)));

        if ($nullRows === \array_slice($expectedRows, 0, $nullCount)) {
            return [...$rowsWithValue, ...$nullRows];
        }

        if ($nullRows === \array_slice($expectedRows, -$nullCount)) {
            return [...$nullRows, ...$rowsWithValue];
        }

        // the rows aren't sorted by the column that contains NULL (e.g. the default sort by id)
        return $expectedRows;
    }
}
