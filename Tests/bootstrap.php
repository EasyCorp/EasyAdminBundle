<?php

/*
 * This file is part of the EasyAdminBundle.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/*
 * Code inspired by https://github.com/Orbitale/CmsBundle/blob/master/Tests/bootstrap.php
 * (c) Alexandre Rock Ancelet <alex@orbitale.io>
 */
$file = __DIR__.'/../vendor/autoload.php';
if (!file_exists($file)) {
    throw new RuntimeException('Install dependencies using Composer to run the test suite.');
}
$autoload = require $file;

AnnotationRegistry::registerLoader(function ($class) use ($autoload) {
    $autoload->loadClass($class);

    return class_exists($class, false);
});

// Test Setup: remove all the contents in the build/ directory
// (PHP doesn't allow to delete directories unless they are empty)
if (is_dir($buildDir = __DIR__.'/../build')) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($buildDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $fileinfo->isDir() ? rmdir($fileinfo->getRealPath()) : unlink($fileinfo->getRealPath());
    }
}

include __DIR__.'/Fixtures/App/AppKernel.php';

$application = new Application(new AppKernel('default_backend', true));
$application->setAutoExit(false);

// Create database
$input = new ArrayInput(array('command' => 'doctrine:database:create'));
$application->run($input, new NullOutput());

// Create database schema
$input = new ArrayInput(array('command' => 'doctrine:schema:create'));
$application->run($input, new NullOutput());

// Load fixtures of the AppTestBundle
$input = new ArrayInput(array('command' => 'doctrine:fixtures:load', '--no-interaction' => true, '--append' => true));
$application->run($input, new NullOutput());

// Make a copy of the original SQLite database to use the same unmodified database in every test
copy($buildDir.'/test.db', $buildDir.'/original_test.db');

unset($input, $application);
