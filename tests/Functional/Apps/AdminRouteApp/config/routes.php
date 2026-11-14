<?php

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    // the only controller of this app that defines its route with the Symfony #[Route] attribute
    $routes->import('../src/Controller/LegacyRouteDashboardController.php', Kernel::MAJOR_VERSION >= 7 ? 'attribute' : 'annotation');

    // import EasyAdmin routes with pretty URLs enabled
    $routes->import('.', 'easyadmin.routes');
};
