<?php

use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;

$configuration = [
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

if (class_exists(PlaintextPasswordHasher::class)) {
    $configuration['password_hashers'] = [User::class => 'plaintext'];
} else {
    $configuration['encoders'] = [User::class => 'plaintext'];
}

if (class_exists(AuthenticatorManager::class)) {
    $configuration['enable_authenticator_manager'] = true;
} else {
    $configuration['firewalls']['main']['anonymous'] = true;
}

$container->loadFromExtension('security', $configuration);
