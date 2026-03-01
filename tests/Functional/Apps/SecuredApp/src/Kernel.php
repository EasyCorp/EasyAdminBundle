<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\SecuredApp;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
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

final class Kernel extends SymfonyKernel
{
    use MicroKernelTrait;

    public const MAJOR_VERSION = SymfonyKernel::MAJOR_VERSION;

    public function __construct()
    {
        parent::__construct('test', false);
    }

    public function registerBundles(): iterable
    {
        return [
            new DoctrineBundle(),
            new DoctrineFixturesBundle(),
            new EasyAdminBundle(),
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new TwigExtraBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/secured/var/'.$this->environment.'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/secured/var/'.$this->environment.'/log';
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
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
