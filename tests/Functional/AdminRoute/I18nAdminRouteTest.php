<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AdminRoute;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\I18nKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group legacy
 */
class I18nAdminRouteTest extends WebTestCase
{
    private const LOCALES = ['en', 'ja', 'es'];

    protected static function getKernelClass(): string
    {
        return I18nKernel::class;
    }

    protected function setUp(): void
    {
        if (version_compare(\Symfony\Component\HttpKernel\Kernel::VERSION, '5.4.1', '<')) {
            $this->markTestSkipped('AdminRoute attributes require Symfony 5.4.1 or higher');
        }

        parent::setUp();

        $client = static::createClient();

        $buildDir = $client->getKernel()->getContainer()->getParameter('kernel.build_dir');
        $filesystem = new Filesystem();
        $filesystem->touch($buildDir.'/easyadmin_pretty_urls_enabled');

        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $cacheDir = sys_get_temp_dir().'/com.github.easycorp.easyadmin/tests/admin_route_i18n/var/test/cache';
        $filesystem->remove($cacheDir.'/easyadmin_pretty_urls_enabled');

        parent::tearDown();
    }

    public function testDashboardRoutesHaveLocaleVariants(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');
        $routes = $router->getRouteCollection();

        foreach (['admin', 'second_admin'] as $dashboard) {
            foreach (self::LOCALES as $locale) {
                $routeName = $dashboard.'.'.$locale;
                $route = $routes->get($routeName);

                $this->assertNotNull($route, sprintf('Route "%s" should exist', $routeName));
                $this->assertSame($locale, $route->getDefault('_locale'),
                    sprintf('Route "%s" should have _locale default set to "%s"', $routeName, $locale));

                $expectedPrefix = '/'.$locale.'/';
                $this->assertStringStartsWith($expectedPrefix, $route->getPath(),
                    sprintf('Route "%s" path should start with "%s"', $routeName, $expectedPrefix));
            }
        }
    }

    public function testCrudRoutesHaveLocaleVariants(): void
    {
        $client = static::createClient();
        $router = $client->getContainer()->get('router');
        $routes = $router->getRouteCollection();

        $baseRouteName = 'admin_built_in_action_list';

        foreach (self::LOCALES as $locale) {
            $routeName = $baseRouteName.'.'.$locale;
            $route = $routes->get($routeName);

            $this->assertNotNull($route, sprintf('Route "%s" should exist', $routeName));
            $this->assertSame($locale, $route->getDefault('_locale'),
                sprintf('Route "%s" should have _locale default set to "%s"', $routeName, $locale));

            $expectedPath = '/'.$locale.'/admin/built-in-action/index';
            $this->assertSame($expectedPath, $route->getPath(),
                sprintf('Route "%s" should have path "%s"', $routeName, $expectedPath));
        }
    }

    public function testLocaleRoutesAreAccessible(): void
    {
        $client = static::createClient();

        foreach (self::LOCALES as $locale) {
            $client->request('GET', '/'.$locale.'/admin/foo/list');

            $this->assertResponseIsSuccessful(
                sprintf('Request to "/%s/admin/foo/list" should be successful', $locale)
            );
            $this->assertSame('Foo List', $client->getResponse()->getContent());
        }
    }
}
