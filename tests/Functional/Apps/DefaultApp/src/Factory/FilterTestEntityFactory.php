<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FilterTestEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<FilterTestEntity>
 */
final class FilterTestEntityFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return FilterTestEntity::class;
    }

    protected function defaults(): array
    {
        return [];
    }
}
