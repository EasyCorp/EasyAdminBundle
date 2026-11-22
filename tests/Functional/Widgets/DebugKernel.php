<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

/**
 * The DefaultApp test application in debug mode (its own kernel always disables
 * the debug mode), used to check what happens when a widget fails while debugging.
 */
final class DebugKernel extends SymfonyKernel
{
    use MicroKernelTrait;

    public function __construct()
    {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [
            new DoctrineBundle(),
            new DAMADoctrineTestBundle(),
            new EasyAdminBundle(),
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new TwigExtraBundle(),
            new ZenstruckFoundryBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/app_debug/var/'.$this->environment.'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/app_debug/var/'.$this->environment.'/log';
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__).'/Apps/DefaultApp';
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import($this->getProjectDir().'/config/routes.php');
    }

    protected function configureContainer(ContainerBuilder $containerBuilder, LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir().'/config/{packages}/*.php', 'glob');
        $loader->load($this->getProjectDir().'/config/{services}.php', 'glob');
    }
}
