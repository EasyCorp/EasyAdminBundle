<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Context;

use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestContextTest extends TestCase
{
    public function testForTestingWithDefaults(): void
    {
        $context = RequestContext::forTesting();

        self::assertNull($context->getUser());
    }

    public function testForTestingWithCustomRequest(): void
    {
        $request = Request::create('/admin/dashboard');

        $context = RequestContext::forTesting($request);

        self::assertSame($request, $context->getRequest());
        self::assertSame('/admin/dashboard', $context->getRequest()->getPathInfo());
    }
}
