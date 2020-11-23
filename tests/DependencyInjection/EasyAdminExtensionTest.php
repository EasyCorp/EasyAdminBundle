<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EasyAdminExtensionTest extends KernelTestCase
{
    public function testLegacyParameterIsDefined()
    {
        $container = (self::bootKernel())->getContainer();

        self::assertSame([], $container->getParameter('easyadmin.config'), 'The legacy container parameter needed to avoid errors when upgrading from EasyAdmin 2 is defined and empty.');
    }
}
