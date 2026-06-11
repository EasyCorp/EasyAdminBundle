<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\CustomizationApp\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->import('../src/Controller/', Kernel::MAJOR_VERSION >= 7 ? 'attribute' : 'annotation');
    $routes->import('.', 'easyadmin.routes');
};
