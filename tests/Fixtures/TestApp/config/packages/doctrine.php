<?php

$container->loadFromExtension('doctrine', [
    'dbal' => [
        'driver' => 'pdo_sqlite',
        'path' => '%kernel.cache_dir%%/database.sqlite',
    ],

    'orm' => [
        'auto_generate_proxy_classes' => true,
        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
        'auto_mapping' => true,
        //'mappings' => [
        //    'is_bundle' => false,
        //    'type' => 'annotation',
        //    'dir' => '%kernel.project_dir%/src/Entity',
        //    'prefix' => 'TestApp\Entity',
        //    'alias' => 'TestApp',
        //],
    ],
]);
