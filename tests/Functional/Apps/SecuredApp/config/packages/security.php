<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Security\AdminAreaVoter;
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
        // with the default 'affirmative' strategy these rules grant access when any of their
        // attributes is granted; the strategy kernels of this app make them stricter
        ['path' => '^/admin/access-control/role-and-full-login', 'roles' => ['ROLE_ADMIN', 'IS_AUTHENTICATED_FULLY']],
        ['path' => '^/admin/access-control/role-and-custom-voter', 'roles' => ['ROLE_USER', AdminAreaVoter::ADMIN_AREA]],
        // rules that match on route attributes instead of on the path ('attributes' and
        // 'route' options were added in Symfony 6.1)
        ...(Kernel::VERSION_ID >= 60100 ? [
            ['attributes' => ['_controller' => 'AccessControlRuleController::controllerAttribute$'], 'roles' => ['ROLE_ADMIN']],
            ['route' => 'access_control_route_option', 'roles' => ['ROLE_ADMIN']],
        ] : []),
        ['path' => '^/admin/secure', 'roles' => ['ROLE_ADMIN']],
        ['path' => '^/admin', 'roles' => ['ROLE_USER']],
    ],
];

if (Kernel::MAJOR_VERSION >= 6) {
    unset($configuration['enable_authenticator_manager']);
}

$container->loadFromExtension('security', $configuration);
