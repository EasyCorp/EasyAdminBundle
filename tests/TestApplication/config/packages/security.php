<?php

use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManager;

$configuration = [
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
];

if (class_exists(PlaintextPasswordHasher::class)) {
    $configuration['password_hashers'] = [
        User::class => 'plaintext',
        PasswordAuthenticatedUserInterface::class => 'plaintext',
    ];
} else {
    $configuration['encoders'] = [User::class => 'plaintext'];
}

if (class_exists(AuthenticatorManager::class)) {
    $configuration['enable_authenticator_manager'] = true;
} else {
    $configuration['firewalls']['main']['anonymous'] = true;
}

$container->loadFromExtension('security', $configuration);
