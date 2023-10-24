<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\User\InMemoryUser;

$configuration = [
    // TODO: remove this when we require Symfony 7.0 or higher in this project
    'enable_authenticator_manager' => true,

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

// TODO: remove this when we require Symfony 7.0 or higher in this project
if (Kernel::MAJOR_VERSION >= 7) {
    unset($configuration['enable_authenticator_manager']);
}

$container->loadFromExtension('security', $configuration);
