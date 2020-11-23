<?php

use Symfony\Component\Security\Core\User\User;

$container->loadFromExtension('security', [
    'encoders' => [
        User::class => 'plaintext',
    ],

    'providers' => [
        'test_users' => [
            'memory' => [
                'users' => [
                    'admin' => [
                        'password' => '1234',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                ],
            ],
        ],
    ],

    'firewalls' => [
        'main' => [
            'pattern' => '^/',
            'provider' => 'test_users',
            'http_basic' => null,
            'logout' => null,
        ],
    ],

    'access_control' => [
        ['path' => '^/', 'roles' => ['ROLE_ADMIN']],
    ],
]);
