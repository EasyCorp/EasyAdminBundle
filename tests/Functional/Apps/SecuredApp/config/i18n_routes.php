<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    // the prefixes start with '/admin' because the firewall of this app only covers that path
    $routes->import('.', 'easyadmin.routes')
        ->prefix(['en' => '/admin/en', 'es' => '/admin/es']);
};
