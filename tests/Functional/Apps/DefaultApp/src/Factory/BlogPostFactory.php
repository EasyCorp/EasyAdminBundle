<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity\BlogPost;
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
            'slug' => sprintf('BlogPost %d slug', $n),
            'content' => sprintf('BlogPost %d content', $n),
            'createdAt' => new \DateTimeImmutable('2020-01-01 00:00:00'),
            'author' => UserFactory::new(),
        ];
    }
}
