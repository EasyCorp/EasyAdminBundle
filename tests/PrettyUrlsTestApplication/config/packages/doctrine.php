<?php

$container->loadFromExtension('doctrine', [
    'dbal' => [
        'driver' => 'pdo_sqlite',
        'path' => '%kernel.cache_dir%/test_database.sqlite',
    ],

    'orm' => [
        'auto_generate_proxy_classes' => true,
        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
        'auto_mapping' => true,
        'mappings' => [
            'TestEntities' => [
                'is_bundle' => false,
                'type' => 'attribute',
                'dir' => '%kernel.project_dir%/src/Entity',
                'prefix' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\PrettyUrlsTestApplication\Entity',
                'alias' => 'app',
            ],
        ],
    ],
]);
