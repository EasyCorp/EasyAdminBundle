<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude(array('build', 'vendor'))
    ->files()
    ->name('*.php')
;

$config = new PhpCsFixer\Config();

return $config
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHPUnit48Migration:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'fopen_flags' => false,
        'ordered_imports' => true,
        'protected_to_private' => false,
        // Part of @Symfony:risky in PHP-CS-Fixer 2.13.0. To be removed from the config file once upgrading
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced', 'strict' => true],
        // Part of future @Symfony ruleset in PHP-CS-Fixer To be removed from the config file once upgrading
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'single_line_throw' => false,
        // this must be disabled because the output of some tests include NBSP characters
        'non_printable_character' => false,
    ))
;
