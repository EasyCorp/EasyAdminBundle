<?php

$config = [
    'dbal' => (require \dirname(__DIR__, 3).'/test_database.php')('customization'),

    'orm' => [
        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
        'auto_mapping' => true,
        'mappings' => [
            'TestEntities' => [
                'is_bundle' => false,
                'type' => 'attribute',
                'dir' => '%kernel.project_dir%/src/Entity',
                'prefix' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Entity',
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
    $doctrineOrmVersion = Composer\InstalledVersions::getVersion('doctrine/orm');

    // ORM 3.8 deprecates not opting into the schema editor API, which needs DBAL 4.5
    if (null !== $doctrineBundleVersion && version_compare($doctrineBundleVersion, '3.4.0', '>=')
        && null !== $doctrineOrmVersion && version_compare($doctrineOrmVersion, '3.8.0', '>=')
        && null !== $doctrineDbalVersion && version_compare($doctrineDbalVersion, '4.5.0', '>=')) {
        $config['orm']['use_schema_editor_api'] = true;
    }

    if (null !== $doctrineBundleVersion && version_compare($doctrineBundleVersion, '3.1.0', '<')) {
        $config['orm']['controller_resolver'] = [
            'auto_mapping' => false,
        ];

        if (null !== $doctrineOrmVersion && version_compare($doctrineOrmVersion, '3.4.0', '>=') && \PHP_VERSION_ID >= 80400) {
            $config['orm']['enable_native_lazy_objects'] = true;
        }

        // ORM 3 always behaves like this; ORM 2 deprecates not opting into it
        if (null !== $doctrineOrmVersion && version_compare($doctrineOrmVersion, '3.0.0', '<')) {
            $config['orm']['enable_lazy_ghost_objects'] = true;
            $config['orm']['report_fields_where_declared'] = true;
        }
    }
}

$container->setParameter('env(TEST_TOKEN)', '');
$container->loadFromExtension('doctrine', $config);
