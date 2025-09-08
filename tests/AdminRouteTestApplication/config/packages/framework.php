<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'secret' => 'test_admin_route_secret',
        'test' => true,
        'router' => [
            'utf8' => true,
        ],
        'session' => [
            'handler_id' => null,
            'storage_factory_id' => 'session.storage.factory.mock_file',
        ],
        'profiler' => false,
        'property_info' => [
            'enabled' => true,
        ],
        'php_errors' => [
            'log' => true,
        ],
        'cache' => [
            'pools' => [
                'cache.easyadmin' => [
                    'adapter' => 'cache.adapter.filesystem',
                ],
            ],
        ],
    ]);
};
