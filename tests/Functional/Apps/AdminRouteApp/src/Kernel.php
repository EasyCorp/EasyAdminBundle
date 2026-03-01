<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\TwigComponent\TwigComponentBundle;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const MAJOR_VERSION = BaseKernel::MAJOR_VERSION;

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/admin_route/var/'.$this->environment.'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/admin_route/var/'.$this->environment.'/log';
    }

    /**
     * @return iterable<BundleInterface>
     */
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new EasyAdminBundle(),
        ];
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir().'/config/services.php');
        $loader->load($this->getProjectDir().'/config/packages/*.php', 'glob');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import($this->getProjectDir().'/config/routes.php');
    }
}
