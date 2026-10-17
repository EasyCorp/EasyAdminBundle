<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\FormTestEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<FormTestEntity>
 */
final class FormTestEntityFactory extends PersistentObjectFactory
{
    private static int $sequence = 0;

    public static function class(): string
    {
        return FormTestEntity::class;
    }

    protected function defaults(): array
    {
        $n = ++self::$sequence;

        return [
            'name' => sprintf('FormTestEntity %d', $n),
        ];
    }
}
