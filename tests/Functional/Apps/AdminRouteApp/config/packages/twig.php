<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->extension('twig', [
        'default_path' => '%kernel.project_dir%/templates',
        'debug' => '%kernel.debug%',
        'strict_variables' => '%kernel.debug%',
    ]);
};
