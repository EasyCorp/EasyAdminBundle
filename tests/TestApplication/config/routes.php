<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\TestApplication\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->import('../src/Controller/', Kernel::MAJOR_VERSION >= 7 ? 'attribute' : 'annotation');
};
