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
                    'alias' => 'AdminRouteTestApplication',
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
            if (null !== $doctrineOrmVersion && version_compare($doctrineOrmVersion, '3.4.0', '>=')) {
                $config['orm']['enable_native_lazy_objects'] = true;
            }
        }
    }

    $container->extension('doctrine', $config);
};
