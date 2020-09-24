<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ServicesTest extends KernelTestCase
{
    public function testMakerCommandServices()
    {
        $application = new Application(self::bootKernel());

        $command = $application->find('list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('make:admin:crud', $output);
        self::assertStringContainsString('make:admin:dashboard', $output);
        self::assertStringContainsString('make:admin:migration', $output);
    }
}
