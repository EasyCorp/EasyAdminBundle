<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use TestApp\Kernel;

class EasyAdminExtensionTest extends TestCase
{
    public function testLegacyParameterIsDefined()
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        self::assertSame([], $container->getParameter('easyadmin.config'), 'The legacy container parameter needed to avoid errors when upgrading from EasyAdmin 2 is defined and empty.');
    }
}
