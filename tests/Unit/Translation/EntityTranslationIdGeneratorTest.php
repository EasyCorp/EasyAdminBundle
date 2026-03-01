<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Translation;

use EasyCorp\Bundle\EasyAdminBundle\Translation\EntityTranslationIdGenerator;
use PHPUnit\Framework\TestCase;

class EntityTranslationIdGeneratorTest extends TestCase
{
    private EntityTranslationIdGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new EntityTranslationIdGenerator();
    }

    public function testGenerateForEntitySingular(): void
    {
        $result = $this->generator->generateForEntity('App\Entity\BlogPost', true);

        $this->assertSame('entities.App\Entity\BlogPost.singular', $result);
    }

    public function testGenerateForEntityPlural(): void
    {
        $result = $this->generator->generateForEntity('App\Entity\BlogPost', false);

        $this->assertSame('entities.App\Entity\BlogPost.plural', $result);
    }

    public function testGenerateForProperty(): void
    {
        $result = $this->generator->generateForProperty('App\Entity\BlogPost', 'title');

        $this->assertSame('entities.App\Entity\BlogPost.properties.title', $result);
    }

    public function testGenerateForPropertyWithCamelCaseName(): void
    {
        $result = $this->generator->generateForProperty('App\Entity\BlogPost', 'publishedAt');

        $this->assertSame('entities.App\Entity\BlogPost.properties.publishedAt', $result);
    }

    public function testGenerateForEntityWithDeepNamespace(): void
    {
        $result = $this->generator->generateForEntity('App\Domain\Blog\Entity\Comment', true);

        $this->assertSame('entities.App\Domain\Blog\Entity\Comment.singular', $result);
    }

    public function testGenerateForPropertyWithDeepNamespace(): void
    {
        $result = $this->generator->generateForProperty('App\Domain\Blog\Entity\Comment', 'content');

        $this->assertSame('entities.App\Domain\Blog\Entity\Comment.properties.content', $result);
    }
}
