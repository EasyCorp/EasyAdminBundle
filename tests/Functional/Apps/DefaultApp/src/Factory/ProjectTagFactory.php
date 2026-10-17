<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\ProjectDomain\ProjectTag;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProjectTag>
 */
final class ProjectTagFactory extends PersistentObjectFactory
{
    private static int $sequence = 0;

    public static function class(): string
    {
        return ProjectTag::class;
    }

    protected function defaults(): array
    {
        $n = ++self::$sequence;

        return [
            'name' => sprintf('ProjectTag %d', $n),
        ];
    }
}
