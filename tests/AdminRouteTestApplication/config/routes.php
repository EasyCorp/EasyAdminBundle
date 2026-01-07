<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    // Import EasyAdmin routes with pretty URLs enabled
    $routes->import('.', 'easyadmin.routes');
};
