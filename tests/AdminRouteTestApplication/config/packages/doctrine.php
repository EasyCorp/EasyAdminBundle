<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $config = [
        'dbal' => [
            'url' => 'sqlite:///:memory:',
        ],
        'orm' => [
            'auto_mapping' => true,
            'mappings' => [
                'AdminRouteTestApplication' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/src/Entity',
                    'prefix' => 'EasyCorp\Bundle\EasyAdminBundle\Tests\AdminRouteTestApplication\Entity',
                    'alias' => 'AdminRouteTestApplication',
                ],
            ],
        ],
    ];

    // doctrine-bundle 2.x compatibility
    if (class_exists(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\CacheCompatibilityPass::class)) {
        $config['orm']['auto_generate_proxy_classes'] = true;
    }

    // TODO: make this config option unconditional when rising the Symfony requirements to 6.4
    // this option was added in doctrine-bundle PR 1554, released as Doctrine Bundle 2.7.1 (https://github.com/doctrine/DoctrineBundle/releases/tag/2.7.1)
    if (class_exists(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ControllerResolverPass::class)) {
        $config['orm']['controller_resolver'] = [
            'auto_mapping' => false,
        ];
    }

    $container->extension('doctrine', $config);
};
