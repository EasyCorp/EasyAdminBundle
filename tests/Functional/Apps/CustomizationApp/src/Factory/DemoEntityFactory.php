<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\DemoEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DemoEntity>
 */
final class DemoEntityFactory extends PersistentObjectFactory
{
    private static int $sequence = 0;

    public static function class(): string
    {
        return DemoEntity::class;
    }

    protected function defaults(): array
    {
        $n = ++self::$sequence;

        return [
            'name' => sprintf('DemoEntity %d', $n),
        ];
    }
}
