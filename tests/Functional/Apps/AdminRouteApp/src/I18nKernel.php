<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp;

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class I18nKernel extends Kernel
{
    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/admin_route_i18n/var/'.$this->environment.'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/admin_route_i18n/var/'.$this->environment.'/log';
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import($this->getProjectDir().'/config/i18n_routes.php');
    }
}
