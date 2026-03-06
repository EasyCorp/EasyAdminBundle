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
}
