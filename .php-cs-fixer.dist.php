<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude('vendor')
    ->files()
    ->name('*.php')
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHPUnit48Migration:risky' => true,
        'fopen_flags' => false,
        'protected_to_private' => false,
        'native_function_invocation' => ['exclude' => ['sprintf']],
        // Part of future @Symfony ruleset in PHP-CS-Fixer To be removed from the config file once upgrading
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'single_line_throw' => false,
        // this must be disabled because the output of some tests include NBSP characters
        'non_printable_character' => false,
        'blank_line_between_import_groups' => false,
        'no_trailing_comma_in_singleline' => false,
        'nullable_type_declaration_for_default_null_value' => true,
        'phpdoc_to_comment' => false,
        // Override @Symfony ruleset to keep mixed return type for PHPStan
        'no_superfluous_phpdoc_tags' => ['allow_hidden_params' => true, 'allow_mixed' => true, 'remove_inheritdoc' => true],
    ])
;
