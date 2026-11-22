<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Widgets;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\WidgetsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\WidgetsInterfaceDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\Widgets\WidgetsInterfaceLazyDashboardController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Checks the route that serves the contents of widgets in the dashboards of DefaultApp
 * (the route options are checked in AdminRouteApp, see WidgetRouteTest).
 */
class WidgetsRoutingTest extends WebTestCase
{
    public function testDashboardUrlIsGeneratedWithTheDashboardRoute(): void
    {
        $client = static::createClient();

        $this->assertSame('widgets_admin', static::getContainer()->get(AdminRouteGenerator::class)->findRouteName(WidgetsDashboardController::class));

        $url = static::getContainer()->get(AdminUrlGeneratorInterface::class)->setDashboard(WidgetsDashboardController::class)->generateUrl();
        $this->assertSame('http://localhost/widgets-admin', $url);

        $crawler = $client->request('GET', '/widgets-admin');
        $this->assertSame('http://localhost/widgets-admin', $crawler->filter('#main-menu a.ea-sidebar-item-link')->first()->attr('href'));
    }

    public function testDashboardRouteNameOfFragmentRequests(): void
    {
        $client = static::createClient();
        $client->request('GET', '/widgets-admin/_widget/stat-1');

        $this->assertResponseIsSuccessful();
        $this->assertRouteSame('widgets_admin_widget', ['widgetName' => 'stat-1']);
    }

    public function testDashboardThatOnlyImplementsTheInterfaceHasNoWidgetRoute(): void
    {
        $client = static::createClient();
        $routes = static::getContainer()->get('router')->getRouteCollection();

        $this->assertNotNull($routes->get('widgets_interface_admin'));
        $this->assertNull($routes->get('widgets_interface_admin_widget'));
        $this->assertNull($routes->get('widgets_interface_lazy_admin_widget'));

        $client->request('GET', '/widgets-interface-admin/_widget/stat-1');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDashboardThatOnlyImplementsTheInterfaceWorksWithoutWidgets(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-interface-admin');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.ea-widget-grid'));
        $this->assertCount(0, $crawler->filter('.ea-widget'));
        $this->assertSame('There is nothing to show here yet.', $crawler->filter('.ea-widget-grid .ea-empty-state-message')->text());
    }

    public function testLazyWidgetsNeedTheAbstractDashboardController(): void
    {
        $client = static::createClient();
        $client->catchExceptions(false);

        $this->expectExceptionMessage(sprintf('The widget "lazy-stat" uses "setLazy()" or "setRefreshInterval()", which load the widget contents in a separate request served by the "renderWidget()" method of the dashboard controller. The "%s" class doesn\'t have that method because it doesn\'t extend "EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController".', WidgetsInterfaceLazyDashboardController::class));

        $client->request('GET', '/widgets-interface-lazy-admin');
    }

    /**
     * The retry button loads the contents of the widget from its URL, which this dashboard doesn't have.
     */
    public function testFailedWidgetHasNoRetryButtonWhenTheDashboardCantServeItsContents(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/widgets-interface-error-admin');

        $this->assertResponseIsSuccessful();
        $failingWidget = $crawler->filter('.ea-widget[data-ea-widget="failing-stat"]');
        $this->assertNull($failingWidget->attr('data-ea-widget-url'));
        $this->assertCount(1, $failingWidget->filter('.ea-empty-state.ea-empty-state-error'));
        $this->assertCount(0, $failingWidget->filter('[data-ea-widget-retry], .ea-empty-state-actions'));
        $this->assertCount(0, $crawler->filter('script[src*="/widgets."]'));
    }

    /**
     * This app imports the route attributes of its controllers, so a #[Route] attribute
     * inherited by the index() method would add a second route to the dashboard.
     */
    public function testDashboardThatInheritsIndexOnlyHasTheRouteOfItsAdminDashboardAttribute(): void
    {
        static::createClient();

        $routeNames = [];
        foreach (static::getContainer()->get('router')->getRouteCollection() as $routeName => $route) {
            if (WidgetsDashboardController::class.'::index' === $route->getDefault('_controller')) {
                $routeNames[] = $routeName;
            }
        }

        $this->assertFalse(WidgetsDashboardController::class === (new \ReflectionMethod(WidgetsDashboardController::class, 'index'))->getDeclaringClass()->getName());
        $this->assertSame(['widgets_admin'], $routeNames);
    }

    public function testInterfaceDashboardIsNotAffectedByOtherDashboards(): void
    {
        static::createClient();

        $this->assertSame('widgets_interface_admin', static::getContainer()->get(AdminRouteGenerator::class)->findRouteName(WidgetsInterfaceDashboardController::class));
    }
}
