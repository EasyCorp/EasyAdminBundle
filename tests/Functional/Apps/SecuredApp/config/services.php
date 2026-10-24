<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $container->parameters()->set('locale', 'en');

    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
    ;

    $services->load('EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\Functional\\Apps\\SecuredApp\\', '../src/*')
        ->exclude('../{Entity,Tests,Kernel.php}');

    $services->load('EasyCorp\\Bundle\\EasyAdminBundle\\Tests\\Functional\\Apps\\SecuredApp\\Controller\\', '../src/Controller/')
        ->tag('controller.service_arguments');
};
