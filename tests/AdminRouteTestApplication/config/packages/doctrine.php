<?php
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $options = [
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

    if (!class_exists(\Doctrine\Common\Annotations\AnnotationReader::class)) {
        // This is Doctrine 3 (annotations package was removed)
        $options['orm']['auto_generate_proxy_classes'] = true;
    }

    $container->extension('doctrine', $options);
};
