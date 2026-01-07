<?php

$configuration = [
    'secret' => 'F00',
    'csrf_protection' => true,
    'http_method_override' => true,
    'session' => [
        'handler_id' => null,
        'storage_factory_id' => 'session.storage.factory.mock_file',
        'cookie_secure' => 'auto',
        'cookie_samesite' => 'lax',
    ],
    'php_errors' => [
        'log' => true,
    ],
    'test' => true,
    'handle_all_throwables' => true,
    'validation' => [
        'email_validation_mode' => 'html5',
    ],
    'uid' => [
        'default_uuid_version' => 7,
        'time_based_uuid_version' => 7,
    ],
];

if (EasyCorp\Bundle\EasyAdminBundle\Tests\PrettyUrlsTestApplication\Kernel::VERSION_ID < 60000) {
    unset($configuration['handle_all_throwables']);

    $configuration['uid']['default_uuid_version'] = 6;
    $configuration['uid']['time_based_uuid_version'] = 1;
}

$container->loadFromExtension('framework', $configuration);
