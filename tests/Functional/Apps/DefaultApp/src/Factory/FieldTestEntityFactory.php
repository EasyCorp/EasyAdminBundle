<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FieldTestEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<FieldTestEntity>
 */
final class FieldTestEntityFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return FieldTestEntity::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}
