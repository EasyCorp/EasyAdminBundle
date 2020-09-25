<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServicesTest extends KernelTestCase
{
    /**
     * @dataProvider provideCommands
     */
    public function testMakerCommandServices(string $commandName)
    {
        $application = new Application(self::bootKernel());

        $command = $application->find($commandName);
        $this->assertSame($commandName, $command->getName());
    }

    public function provideCommands()
    {
        yield ['make:admin:crud'];
        yield ['make:admin:dashboard'];
        yield ['make:admin:migration'];
    }
}
