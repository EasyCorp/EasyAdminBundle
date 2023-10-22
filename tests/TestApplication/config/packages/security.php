<?php

use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\HttpKernel\Kernel;

$configuration = [
    'password_hashers' => [
        InMemoryUser::class => 'plaintext',
    ],

    'providers' => [
        'test_users' => [
            'memory' => [
                'users' => [
                    'user' => [
                        'password' => '1234',
                        'roles' => ['ROLE_USER'],
                    ],
                    'admin' => [
                        'password' => '1234',
                        'roles' => ['ROLE_ADMIN'],
                    ],
                ],
            ],
        ],
    ],

    'firewalls' => [
        'secure_admin' => [
            'pattern' => '^/secure_admin',
            'provider' => 'test_users',
            'http_basic' => null,
            'logout' => null,
        ],
    ],

    'role_hierarchy' => [
        'ROLE_ADMIN' => ['ROLE_USER'],
    ],

    'access_control' => [
        ['path' => '^/secure_admin', 'roles' => ['ROLE_USER']],
    ],
];

if (Kernel::MAJOR_VERSION < 7) {
    $configuration['enable_authenticator_manager'] = true;
}

$container->loadFromExtension('security', $configuration);
