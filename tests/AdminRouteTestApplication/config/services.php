<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->public();

    $services->load('EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\AdminRouteTestApplication\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments');
};
