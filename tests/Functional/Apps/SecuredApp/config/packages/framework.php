<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;

$configuration = [
    'secret' => 'test_secret',
    'test' => true,
    'session' => [
        'storage_factory_id' => 'session.storage.factory.mock_file',
    ],
    'router' => [
        'utf8' => true,
    ],
    'http_method_override' => false,
    'php_errors' => [
        'log' => true,
    ],
];

if (Kernel::MAJOR_VERSION >= 6) {
    $configuration['handle_all_throwables'] = true;
}
if (Symfony\Component\HttpKernel\Kernel::VERSION_ID >= 70300) {
    $configuration['property_info'] = [
        'with_constructor_extractor' => true,
    ];
}

$container->loadFromExtension('framework', $configuration);
