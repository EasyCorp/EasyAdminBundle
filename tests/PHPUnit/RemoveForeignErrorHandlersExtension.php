<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\PHPUnit;

use PHPUnit\Event\TestRunner\ExecutionStarted;
use PHPUnit\Event\TestRunner\ExecutionStartedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * Foundry boots the kernel of the tests that use factories in their data providers,
 * which PHPUnit calls before running any test. Booting FrameworkBundle registers
 * Symfony's ErrorHandler globally, and PHPUnit doesn't install its own error handler
 * when another one exists, so no test would report (or expect) deprecations, notices
 * and warnings. This removes those handlers right before the first test runs.
 */
final class RemoveForeignErrorHandlersExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscriber(new class implements ExecutionStartedSubscriber {
            public function notify(ExecutionStarted $event): void
            {
                while (null !== set_error_handler(null)) {
                    restore_error_handler();
                    restore_error_handler();
                }
                restore_error_handler();
            }
        });
    }
}
