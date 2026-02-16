<?php

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;

// needed to avoid encoding issues when running tests on different platforms
setlocale(\LC_ALL, 'en_US.UTF-8');

// needed to avoid failed tests when other timezones than UTC are configured for PHP
date_default_timezone_set('UTC');

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies using Composer to run the test suite.');
}
$autoload = require $file;

/**
 * Helper function to initialize a test application database with fixtures.
 */
function initializeTestAppDatabase(object $kernel, Filesystem $filesystem): void
{
    // delete the existing cache directory to avoid issues
    $filesystem->remove($kernel->getCacheDir());

    $application = new Application($kernel);
    $application->setAutoExit(false);

    $input = new ArrayInput(['command' => 'doctrine:database:drop', '--no-interaction' => true, '--force' => true]);
    $application->run($input, new ConsoleOutput());

    $input = new ArrayInput(['command' => 'doctrine:database:create', '--no-interaction' => true]);
    $application->run($input, new ConsoleOutput());

    $input = new ArrayInput(['command' => 'doctrine:schema:create']);
    $application->run($input, new ConsoleOutput());

    $input = new ArrayInput(['command' => 'doctrine:fixtures:load', '--no-interaction' => true, '--append' => false]);
    $application->run($input, new ConsoleOutput());

    $kernel->shutdown();
}

$filesystem = new Filesystem();

$testAppKernels = [
    EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Kernel::class,
    EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel::class,
    EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\UglyUrlsApp\Kernel::class,
    EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel::class,
];

foreach ($testAppKernels as $kernelClass) {
    initializeTestAppDatabase(new $kernelClass(), $filesystem);
}
