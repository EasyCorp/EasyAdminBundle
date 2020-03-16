<?php

// needed to avoid encoding issues when running tests on different platforms
setlocale(LC_ALL, 'en_US.UTF-8');

$autoloadFile = __DIR__.'/../vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    throw new RuntimeException('Install dependencies using Composer to run the test suite.');
}

require $autoloadFile;
