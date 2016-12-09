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
        'binary_operator_spaces' => array(
            'align_double_arrow' => false,
        ),
        'phpdoc_summary' => false,
    ))
;
