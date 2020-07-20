<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\User;

class FunctionalTest extends TestCase
{
    public function testLegacyParameterIsDefined()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        self::assertSame([], $container->getParameter('easyadmin.config'), 'The legacy container parameter needed to avoid errors when upgrading from EasyAdmin 2 is defined and empty.');
    }

    public function testMakerCommandsAreCreated()
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        $application = new Application($kernel);

        $command = $application->find('list');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertStringContainsString('make:admin:crud', $output);
        self::assertStringContainsString('make:admin:dashboard', $output);
        self::assertStringContainsString('make:admin:migration', $output);
    }
}


class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/var/'.$this->environment.'/cache';
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/var/'.$this->environment.'/log';
    }

    public function registerBundles()
    {
        return [
            new DoctrineBundle(),
            new FrameworkBundle(),
            new TwigBundle(),
            new SecurityBundle(),
            new EasyAdminBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader)
    {
        $container->loadFromExtension('framework', [
            'secret' => 'F00',
        ]);

        $container->loadFromExtension('doctrine', [
            'dbal' => ['url' => 'db_url'],
            'orm' => [],
        ]);

        $container->loadFromExtension('twig', [
            'default_path' => '%kernel.project_dir%/templates',
        ]);

        $container->loadFromExtension('security', [
            'encoders' => [
                User::class => 'plaintext',
            ],
            'providers' => [
                'test_users' => [
                    'memory' => [
                        'users' => [
                            'admin' => [
                                'password' => '1234',
                                'roles' => ['ROLE_ADMIN'],
                            ],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'main' => [
                    'pattern' => '^/',
                    'provider' => 'test_users',
                    'http_basic' => null,
                ],
            ],
            'access_control' => [
                ['path' => '^/', 'roles' => ['ROLE_ADMIN']],
            ],
        ]);
    }
}
