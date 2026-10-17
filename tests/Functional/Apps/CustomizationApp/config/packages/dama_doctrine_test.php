<?php

$container->loadFromExtension('dama_doctrine_test', [
    // each test app has its own database, so they can't share the same static connection
    'connection_keys' => [
        'default' => 'customization_app',
    ],
]);
