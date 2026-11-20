<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Context;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use PHPUnit\Framework\TestCase;

class AdminContextTest extends TestCase
{
    public function testGetEntityThrowsExceptionWhenNotInCrudContext(): void
    {
        $context = AdminContext::forTesting();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot get entity outside of a CRUD context');

        $context->getEntity();
    }

    public function testOverrideTemplate(): void
    {
        $context = AdminContext::forTesting();

        $context->overrideTemplate('crud/index', 'admin/index.html.twig');

        self::assertSame('admin/index.html.twig', $context->getTemplatePath('crud/index'));
        self::assertSame('@EasyAdmin/crud/detail.html.twig', $context->getTemplatePath('crud/detail'));
    }
}
