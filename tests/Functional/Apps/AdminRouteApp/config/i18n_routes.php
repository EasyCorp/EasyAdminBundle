<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->import('.', 'easyadmin.routes')
        ->prefix(['en' => '/en', 'ja' => '/ja', 'es' => '/es']);
};
