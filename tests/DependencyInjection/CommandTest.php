<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommandTest extends KernelTestCase
{
    /**
     * @dataProvider provideCommands
     */
    public function testMakerCommandServices(string $commandName): void
    {
        $application = new Application(self::bootKernel());

        $command = $application->find($commandName);

        // It's not enough to test that $this->assertTrue($commandName, $command->getName());
        // because lazy commands are not instantiated to get their names.
        // Instead, get the command help to force its instantiation and then
        // check that help is not empty
        $this->assertNotEmpty($command->getHelp());
    }

    public static function provideCommands(): \Generator
    {
        yield ['make:admin:crud'];
        yield ['make:admin:dashboard'];
    }
}
