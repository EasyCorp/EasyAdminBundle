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

    // widgets are value objects with a private constructor, so they can't be controllers
    $services->load('EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\Functional\\Apps\\DefaultApp\\Controller\\', '../src/Controller/')
        ->exclude('../src/Controller/Widgets/Contract/*Widget.php')
        ->tag('controller.service_arguments');
};
