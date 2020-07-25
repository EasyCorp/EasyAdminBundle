<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use TestApp\Kernel;

class ServicesTest extends TestCase
{
    public function testMakerCommandServices()
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        $application = new Application($kernel);

        $command = $application->find('list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('make:admin:crud', $output);
        self::assertStringContainsString('make:admin:dashboard', $output);
        self::assertStringContainsString('make:admin:migration', $output);
    }
}
