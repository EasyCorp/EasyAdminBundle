<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Context;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class AdminContextTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testGetReferrerEmptyString(): void
    {
        $request = new Request(query: [EA::REFERRER => '']);

        $target = AdminContext::forTesting(
            requestContext: RequestContext::forTesting($request),
        );

        self::assertNull($target->getReferrer());
    }

    public function testGetEntityThrowsExceptionWhenNotInCrudContext(): void
    {
        $context = AdminContext::forTesting();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot get entity outside of a CRUD context');

        $context->getEntity();
    }
}
