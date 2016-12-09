<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude(array('build', 'vendor'))
    ->files()
    ->name('*.php')
;

return PhpCsFixer\Config::create()
    ->setUsingCache(true)
    ->setFinder($finder)
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => false,
        'array_syntax' => array('syntax' => 'long'),
        'binary_operator_spaces' => array(
            'align_double_arrow' => false,
        ),
        'combine_consecutive_unsets' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => true,
        'php_unit_strict' => true,
        'phpdoc_short_description' => false,
        'phpdoc_summary' => false,
        'psr4' => true,
        'strict_comparison' => true,
    ))
;
