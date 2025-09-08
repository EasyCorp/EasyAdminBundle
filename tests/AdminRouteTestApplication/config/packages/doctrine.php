<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->extension('doctrine', [
        'dbal' => [
            'url' => 'sqlite:///:memory:',
        ],
        'orm' => [
            'auto_generate_proxy_classes' => true,
            'auto_mapping' => true,
            'mappings' => [
                'AdminRouteTestApplication' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/src/Entity',
                    'prefix' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Entity',
                    'alias' => 'AdminRouteTestApplication',
                ],
            ],
        ],
    ]);
};
