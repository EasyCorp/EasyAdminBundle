<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Context;

use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use PHPUnit\Framework\TestCase;

class CrudContextTest extends TestCase
{
    public function testForTestingCreatesDefaultCrudDto(): void
    {
        $context = CrudContext::forTesting();

        self::assertInstanceOf(CrudDto::class, $context->getCrud());
        self::assertNull($context->getEntity());
        self::assertNull($context->getSearch());
    }
}
