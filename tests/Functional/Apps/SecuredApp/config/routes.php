<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->import('../src/Controller/', 'attribute');
    $routes->import('.', 'easyadmin.routes');

    // aliases of admin routes, used to check that they get the same permission checks as the admin routes
    $routes->alias('send_report_alias', 'secured_admin_custom_action_access_send_report');
    $routes->alias('send_reminder_alias', 'secured_admin_custom_action_access_send_reminder');
};
