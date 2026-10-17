<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\Project;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Project>
 */
final class ProjectFactory extends PersistentObjectFactory
{
    private static int $sequence = 0;

    public static function class(): string
    {
        return Project::class;
    }

    protected function defaults(): array
    {
        $n = ++self::$sequence;

        return [
            'name' => sprintf('Project %d', $n),
            'description' => sprintf('Project %d description', $n),
            'startDateMutable' => new \DateTime('2020-01-01 00:00:00'),
            'startDateImmutable' => new \DateTimeImmutable('2020-01-01 00:00:00'),
            'startDateTimeMutable' => new \DateTime('2020-01-01 00:00:00'),
            'startDateTimeImmutable' => new \DateTimeImmutable('2020-01-01 00:00:00'),
            'startDateTimeTzMutable' => new \DateTime('2020-01-01 00:00:00'),
            'startDateTimeTzImmutable' => new \DateTimeImmutable('2020-01-01 00:00:00'),
            'countInteger' => $n,
            'countSmallint' => $n,
            'priceDecimal' => '1.00',
            'priceFloat' => (float) $n,
            'startTimeMutable' => new \DateTime('2020-01-01 00:00:00'),
            'startTimeImmutable' => new \DateTimeImmutable('2020-01-01 00:00:00'),
            // Doctrine stores an empty simple_array as NULL, which this NOT NULL column rejects
            'statesSimpleArray' => ['active'],
        ];
    }
}
