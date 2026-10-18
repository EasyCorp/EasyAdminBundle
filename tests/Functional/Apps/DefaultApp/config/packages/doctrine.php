<?php

$config = [
    'dbal' => [
        'driver' => 'pdo_sqlite',
        // ParaTest workers share the compiled container but each one needs its own database
        'path' => '%kernel.cache_dir%/test_database%env(TEST_TOKEN)%.sqlite',
    ],

    'orm' => [
        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
        'auto_mapping' => true,
        'mappings' => [
            'TestEntities' => [
                'is_bundle' => false,
                'type' => 'attribute',
                'dir' => '%kernel.project_dir%/src/Entity',
                'prefix' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Entity',
                'alias' => 'app',
            ],
        ],
    ],
];

if (class_exists(Composer\InstalledVersions::class)) {
    // DAMADoctrineTestBundle needs savepoints, which DBAL 4 always enables
    $doctrineDbalVersion = Composer\InstalledVersions::getVersion('doctrine/dbal');
    if (null !== $doctrineDbalVersion && version_compare($doctrineDbalVersion, '4.0.0', '<')) {
        $config['dbal']['use_savepoints'] = true;
    }

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

$container->setParameter('env(TEST_TOKEN)', '');
$container->loadFromExtension('doctrine', $config);
