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
                'dir' => '%kernel.project_dir%/../DefaultApp/src/Entity',
                'prefix' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity',
                'alias' => 'app',
            ],
        ],
    ],
];

if (class_exists(Composer\InstalledVersions::class)) {
    $doctrineBundleVersion = Composer\InstalledVersions::getVersion('doctrine/doctrine-bundle');

    if (null !== $doctrineBundleVersion && version_compare($doctrineBundleVersion, '3.1.0', '<')) {
        $config['orm']['controller_resolver'] = [
            'auto_mapping' => false,
        ];

        $doctrineOrmVersion = Composer\InstalledVersions::getVersion('doctrine/orm');
        if (null !== $doctrineOrmVersion && version_compare($doctrineOrmVersion, '3.4.0', '>=') && \PHP_VERSION_ID >= 80400) {
            $config['orm']['enable_native_lazy_objects'] = true;
        }
    }
}

$container->loadFromExtension('doctrine', $config);
