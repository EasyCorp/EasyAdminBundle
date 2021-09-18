<?php

use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorageFactory;

$configuration = [
    'secret' => 'F00',
    'csrf_protection' => true,
    'session' => [
        'handler_id' => null,
    ],
    'test' => true,
];

if (class_exists(NativeSessionStorageFactory::class)) {
    $configuration['session'] = ['storage_factory_id' => 'session.storage.factory.mock_file'];
} else {
    $configuration['session'] = ['storage_id' => 'session.storage.mock_file'];
}

$container->loadFromExtension('framework', $configuration);
