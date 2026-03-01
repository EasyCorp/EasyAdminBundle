<?php

$configuration = [
    'secret' => 'test_admin_route_secret',
    'http_method_override' => true,
    'test' => true,
    'router' => [
        'utf8' => true,
    ],
    'session' => [
        'handler_id' => null,
        'storage_factory_id' => 'session.storage.factory.mock_file',
        'cookie_secure' => 'auto',
        'cookie_samesite' => 'lax',
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
    'handle_all_throwables' => true,
    'validation' => [
        'email_validation_mode' => 'html5',
    ],
    'uid' => [
        'default_uuid_version' => 7,
        'time_based_uuid_version' => 7,
    ],
];

if (EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Kernel::VERSION_ID < 60000) {
    unset($configuration['handle_all_throwables']);

    $configuration['uid']['default_uuid_version'] = 6;
    $configuration['uid']['time_based_uuid_version'] = 1;
}

if (Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 70300) {
    $configuration['property_info'] = [
        'with_constructor_extractor' => true,
    ];
}

$container->loadFromExtension('framework', $configuration);
