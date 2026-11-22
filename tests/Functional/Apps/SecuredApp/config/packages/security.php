<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security\RequestSubjectVoter;
use Symfony\Component\Security\Core\User\InMemoryUser;

$configuration = [
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
                    'super_admin' => [
                        'password' => '1234',
                        'roles' => ['ROLE_SUPER_ADMIN'],
                    ],
                ],
            ],
        ],
    ],

    'firewalls' => [
        'admin' => [
            'pattern' => '^/admin',
            'provider' => 'test_users',
            'http_basic' => null,
            'logout' => null,
        ],
    ],

    'role_hierarchy' => [
        'ROLE_ADMIN' => ['ROLE_USER'],
        'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN'],
    ],

    'access_control' => [
        ['path' => '^/admin/secure', 'roles' => ['ROLE_ADMIN']],
        // these rules match the dashboard page but not the URL that serves the contents of its widgets
        ['path' => '^/admin/exact-path-widgets$', 'roles' => ['ROLE_ADMIN']],
        ['route' => '^secured_widgets_route_rule_admin$', 'roles' => ['ROLE_ADMIN']],
        // the voter of this attribute needs the request, which is the subject when Symfony checks these rules
        ['path' => '^/admin/request-subject-widgets$', 'roles' => [RequestSubjectVoter::ADMIN_REQUEST]],
        ['path' => '^/admin', 'roles' => ['ROLE_USER']],
    ],
];

if (Kernel::MAJOR_VERSION >= 6) {
    unset($configuration['enable_authenticator_manager']);
}

$container->loadFromExtension('security', $configuration);
