<?php

namespace TestApp;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class KernelForSymfony5 extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/var/'.$this->environment.'/cache';
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/var/'.$this->environment.'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.php');
        $container->import('../config/{packages}/'.$this->environment.'/*.php');
        $container->import('../config/{services}.php');
        $container->import('../config/{services}_'.$this->environment.'.php');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/{routes}/'.$this->environment.'/*.php');
        $routes->import('../config/{routes}/*.php');
        $routes->import('../config/{routes}.php');
    }
}
