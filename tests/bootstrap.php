<?php

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

// needed to avoid encoding issues when running tests on different platforms
setlocale(\LC_ALL, 'en_US.UTF-8');

// needed to avoid failed tests when other timezones than UTC are configured for PHP
date_default_timezone_set('UTC');

$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies using Composer to run the test suite.');
}
$autoload = require $file;

function compileTestAppContainer(KernelInterface $kernel, Filesystem $filesystem): void
{
    // the test apps don't use the debug mode, so their cache is never refreshed when the code changes
    $filesystem->remove($kernel->getCacheDir());

    $kernel->boot();
    // trigger route compilation so that AdminRouteGenerator::generateAll()
    // populates the EasyAdmin route cache (used by AdminControllerRegistry)
    $kernel->getContainer()->get('router')->getRouteCollection();
}

function createTestAppDatabase(KernelInterface $kernel): void
{
    $application = new Application($kernel);
    $application->setAutoExit(false);

    $kernel->boot();
    // DBAL doesn't support doctrine:database:create for SQLite (the database file is created
    // when connecting to it), but database servers need it
    $isSqlite = str_contains($kernel->getContainer()->get('doctrine')->getConnection()->getParams()['driver'] ?? '', 'sqlite');
    $commands = $isSqlite ? [
        ['command' => 'doctrine:database:drop', '--no-interaction' => true, '--force' => true],
        ['command' => 'doctrine:schema:create'],
    ] : [
        ['command' => 'doctrine:database:drop', '--no-interaction' => true, '--force' => true, '--if-exists' => true],
        ['command' => 'doctrine:database:create', '--no-interaction' => true, '--if-not-exists' => true],
        ['command' => 'doctrine:schema:create'],
    ];

    foreach ($commands as $command) {
        if (0 !== $exitCode = $application->run(new ArrayInput($command), new ConsoleOutput())) {
            throw new RuntimeException(sprintf('The "%s" command failed with exit code %d while creating the database of "%s".', $command['command'], $exitCode, get_debug_type($kernel)));
        }
    }
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

// ParaTest runs this file in its main process before starting the workers (which have a
// TEST_TOKEN), so the containers are compiled once and the workers only create their databases
$isParaTestWorker = false !== getenv('TEST_TOKEN');
$isParaTestMainProcess = !$isParaTestWorker && str_ends_with($_SERVER['argv'][0] ?? '', 'paratest');
foreach ($testAppKernels as $kernelClass) {
    $kernel = new $kernelClass();

    if (!$isParaTestWorker) {
        compileTestAppContainer($kernel, $filesystem);
    }

    if (!$isParaTestMainProcess) {
        createTestAppDatabase($kernel);
    }

    $kernel->shutdown();
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
