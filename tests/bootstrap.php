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

    // trigger route compilation so that AdminRouteGenerator::generateAll()
    // populates the EasyAdmin route cache (used by AdminControllerRegistry)
    $kernel->getContainer()->get('router')->getRouteCollection();

    $kernel->shutdown();
}

$filesystem = new Filesystem();

$testAppKernels = [
    EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Kernel::class,
    EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel::class,
    EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel::class,
];

// symfony/phpunit-bridge used to enable these; keep reporting Doctrine deprecations
// every time they happen instead of only the first time
Doctrine\Deprecations\Deprecation::withoutDeduplication();
Doctrine\Deprecations\Deprecation::enableWithTriggerError();

// PHPUnit only reports the deprecations triggered inside tests, but the containers of the
// test apps are compiled here (and reused by the tests), so collect them while doing that
$bootstrapDeprecations = [];
$collectBootstrapDeprecations = static function (int $type, string $message, string $file, int $line) use (&$bootstrapDeprecations): bool {
    if (\E_DEPRECATED !== $type && \E_USER_DEPRECATED !== $type) {
        return false;
    }

    $bootstrapDeprecations[$message] = sprintf('%s (%s:%d)', $message, $file, $line);

    return true;
};
set_error_handler($collectBootstrapDeprecations);

foreach ($testAppKernels as $kernelClass) {
    initializeTestAppDatabase(new $kernelClass(), $filesystem);
}

// running the console commands above can register Symfony's ErrorHandler globally; PHPUnit
// doesn't install its own error handler when another one exists, so it would never see
// (and report) the deprecations, notices and warnings triggered by the tests
do {
    $currentErrorHandler = set_error_handler(null);
    restore_error_handler();
    restore_error_handler();
} while ($currentErrorHandler !== $collectBootstrapDeprecations && null !== $currentErrorHandler);

if ([] !== $bootstrapDeprecations) {
    fwrite(\STDERR, sprintf("%d deprecations were triggered while initializing the test apps:\n\n* %s\n\n", \count($bootstrapDeprecations), implode("\n* ", $bootstrapDeprecations)));

    if (!\in_array('--do-not-fail-on-deprecation', $_SERVER['argv'] ?? [], true)) {
        exit(1);
    }
}
