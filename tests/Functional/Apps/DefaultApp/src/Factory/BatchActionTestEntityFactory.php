<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\BatchActionTestEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<BatchActionTestEntity>
 */
final class BatchActionTestEntityFactory extends PersistentObjectFactory
{
    private static int $sequence = 0;

    public static function class(): string
    {
        return BatchActionTestEntity::class;
    }

    protected function defaults(): array
    {
        $n = ++self::$sequence;

        return [
            'name' => sprintf('BatchActionTestEntity %d', $n),
        ];
    }
}
