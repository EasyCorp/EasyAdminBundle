<?php

namespace EasyCorp\Bundle\EasyAdminBundle\App\Tests;

use EasyCorp\Bundle\EasyAdminBundle\Maker\Migrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;

class MigratorTest extends TestCase
{
    public function testMigrate()
    {
        $ea2Config = include __DIR__.'/fixtures/input/easyadmin-demo-config-dump.php';
        $outputDir = sprintf('%s/%s', sys_get_temp_dir(), md5(random_bytes(16)));
        (new Filesystem())->mkdir($outputDir);
        $namespace = 'App\\Controller\\Admin';
        $output = new NullOutput();

        (new Migrator())->migrate($ea2Config, $outputDir, $namespace, $output);

        $expectedClasses = glob(__DIR__.'/fixtures/output/*.php');
        foreach ($expectedClasses as $expectedClassFilePath) {
            $classFileName = basename($expectedClassFilePath);
            $this->assertFileEquals($expectedClassFilePath, $outputDir.'/'.$classFileName);
        }
    }
}
