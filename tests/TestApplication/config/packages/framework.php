<?php

$container->loadFromExtension('framework', [
    'secret' => 'F00',
    'csrf_protection' => true,
    'session' => [
        'handler_id' => null,
        'storage_id' => 'session.storage.mock_file',
    ],
    'test' => true,
]);
