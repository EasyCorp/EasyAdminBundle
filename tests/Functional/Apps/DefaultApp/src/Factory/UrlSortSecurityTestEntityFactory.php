<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\Synthetic\UrlSortSecurityTestEntity;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<UrlSortSecurityTestEntity>
 */
final class UrlSortSecurityTestEntityFactory extends PersistentObjectFactory
{
    private static int $sequence = 0;

    public static function class(): string
    {
        return UrlSortSecurityTestEntity::class;
    }

    protected function defaults(): array
    {
        $n = ++self::$sequence;

        return [
            'displayedField' => sprintf('UrlSortSecurityTestEntity %d', $n),
            'hiddenField' => sprintf('UrlSortSecurityTestEntity %d hiddenField', $n),
            'otherField' => sprintf('UrlSortSecurityTestEntity %d otherField', $n),
        ];
    }
}
