#!/usr/bin/env php
<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\ApiReferenceGenerator;

$packageDir = \dirname(__DIR__, 4);

require $packageDir.'/vendor/autoload.php';

// the classes of src/Test/ extend PHPUnit classes and simple-phpunit installs
// PHPUnit in its own directory instead of the main vendor/ directory
if (!class_exists(PHPUnit\Framework\TestCase::class)) {
    $phpUnitAutoloadFiles = glob($packageDir.'/vendor/bin/.phpunit/phpunit-*/vendor/autoload.php') ?: [];
    sort($phpUnitAutoloadFiles, \SORT_NATURAL);

    if ([] === $phpUnitAutoloadFiles) {
        throw new RuntimeException('PHPUnit is not installed, so the classes of the "src/Test/" directory cannot be loaded. Run "php vendor/bin/simple-phpunit --version" to install it and run this script again.');
    }

    require end($phpUnitAutoloadFiles);
}

$outputPath = $packageDir.'/skills/easyadmin/references/api.md';
$outputDir = \dirname($outputPath);
if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
    throw new RuntimeException(sprintf('The "%s" directory cannot be created.', $outputDir));
}

if (false === file_put_contents($outputPath, (new ApiReferenceGenerator($packageDir))->generate())) {
    throw new RuntimeException(sprintf('The "%s" file cannot be written.', $outputPath));
}

echo $outputPath."\n";
