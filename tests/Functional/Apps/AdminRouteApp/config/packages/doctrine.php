<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $config = [
        'dbal' => [
            'url' => 'sqlite:///:memory:',
        ],
        'orm' => [
            'auto_mapping' => true,
            'mappings' => [
                'AdminRouteTestApplication' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/src/Entity',
                    'prefix' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Entity',
                ],
            ],
        ],
    ];

    if (class_exists(Composer\InstalledVersions::class)) {
        $doctrineBundleVersion = Composer\InstalledVersions::getVersion('doctrine/doctrine-bundle');
        $doctrineOrmVersion = Composer\InstalledVersions::getVersion('doctrine/orm');
        $doctrineDbalVersion = Composer\InstalledVersions::getVersion('doctrine/dbal');

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

    $container->extension('doctrine', $config);
};
