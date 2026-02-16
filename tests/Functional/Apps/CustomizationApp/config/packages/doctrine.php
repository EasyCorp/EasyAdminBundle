<?php

$config = [
    'dbal' => [
        'driver' => 'pdo_sqlite',
        'path' => '%kernel.cache_dir%/test_database.sqlite',
    ],

    'orm' => [
        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
        'auto_mapping' => true,
        'mappings' => [
            'TestEntities' => [
                'is_bundle' => false,
                'type' => 'attribute',
                'dir' => '%kernel.project_dir%/src/Entity',
                'prefix' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity',
                'alias' => 'app',
            ],
        ],
    ],
];

if (class_exists(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ControllerResolverPass::class)) {
    $config['orm']['controller_resolver'] = [
        'auto_mapping' => false,
    ];
}

if (class_exists(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\CacheCompatibilityPass::class)) {
    $config['orm']['auto_generate_proxy_classes'] = true;
}

$container->loadFromExtension('doctrine', $config);
