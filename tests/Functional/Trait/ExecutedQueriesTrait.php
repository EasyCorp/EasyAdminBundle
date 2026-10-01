<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Trait;

use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

/**
 * Reads the SQL queries executed by the test app (its DBAL connection has profiling enabled).
 */
trait ExecutedQueriesTrait
{
    protected function resetExecutedQueries(): void
    {
        $this->getDebugDataHolder()->reset();
    }

    /**
     * @return list<string>
     */
    protected function getExecutedQueries(): array
    {
        $queries = [];
        foreach ($this->getDebugDataHolder()->getData()['default'] ?? [] as $query) {
            $queries[] = $query['sql'];
        }

        return $queries;
    }

    /**
     * @return list<string>
     */
    protected function getExecutedSelectQueries(): array
    {
        return array_values(array_filter($this->getExecutedQueries(), static fn (string $sql): bool => str_starts_with($sql, 'SELECT')));
    }

    /**
     * @return list<string>
     */
    protected function getExecutedQueriesMatching(string $regex): array
    {
        return array_values(preg_grep($regex, $this->getExecutedQueries()) ?: []);
    }

    private function getDebugDataHolder(): DebugDataHolder
    {
        /** @var DebugDataHolder $debugDataHolder */
        $debugDataHolder = static::getContainer()->get('doctrine.debug_data_holder');

        return $debugDataHolder;
    }
}
