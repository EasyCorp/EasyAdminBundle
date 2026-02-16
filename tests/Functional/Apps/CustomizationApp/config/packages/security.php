<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;

$configuration = [
    'enable_authenticator_manager' => true,

    'firewalls' => [
        'main' => [
            'lazy' => true,
        ],
    ],
];

if (Kernel::MAJOR_VERSION >= 6) {
    unset($configuration['enable_authenticator_manager']);
}

$container->loadFromExtension('security', $configuration);
