<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    $routes->import('../src/Controller/', Kernel::MAJOR_VERSION >= 7 ? 'attribute' : 'annotation');
    $routes->import('.', 'easyadmin.routes');

    // alias of a plain route protected by an access_control rule that matches on the route name
    if (Kernel::VERSION_ID >= 60100) {
        $routes->alias('route_option_shortcut', 'access_control_route_option');
    }

    // aliases of admin routes, used to check that they get the same permission checks as the admin routes
    $routes->alias('send_report_alias', 'secured_admin_custom_action_access_send_report');
    $routes->alias('send_reminder_alias', 'secured_admin_custom_action_access_send_reminder');
};
