<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('locale', 'en')
        ->set('easyadmin.ui_kit.enabled', true);

    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
    ;

    $services->load('EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\Functional\\Apps\\DefaultApp\\', '../src/*')
        ->exclude('../{Entity,Tests,Kernel.php}');

    $services->load('EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\Functional\\Apps\\DefaultApp\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments');
};
