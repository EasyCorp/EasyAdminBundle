<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\ActionTestEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ActionTestEntity>
 */
final class ActionTestEntityFactory extends PersistentObjectFactory
{
    private static int $sequence = 0;

    public static function class(): string
    {
        return ActionTestEntity::class;
    }

    protected function defaults(): array
    {
        $n = ++self::$sequence;

        return [
            'name' => sprintf('ActionTestEntity %d', $n),
        ];
    }
}
