<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity\BlogPost;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<BlogPost>
 */
final class BlogPostFactory extends PersistentObjectFactory
{
    private static int $sequence = 0;

    public static function class(): string
    {
        return BlogPost::class;
    }

    protected function defaults(): array
    {
        $n = ++self::$sequence;

        return [
            'title' => sprintf('BlogPost %d', $n),
        ];
    }
}
