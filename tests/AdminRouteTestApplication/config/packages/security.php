<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->extension('security', [
        'password_hashers' => [
            'Symfony\Component\Security\Core\User\InMemoryUser' => 'plaintext',
        ],
        'providers' => [
            'test_users' => [
                'memory' => [
                    'users' => [
                        'admin' => ['password' => 'admin', 'roles' => ['ROLE_ADMIN']],
                    ],
                ],
            ],
        ],
        'firewalls' => [
            'main' => [
                'pattern' => '^/',
                'provider' => 'test_users',
                'http_basic' => true,
            ],
        ],
        'access_control' => [],
    ]);
};
