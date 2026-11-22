<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\AdminRoute;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\AdvancedOptionsDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Controller\SecondDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\AdminRouteApp\Kernel;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;

/**
 * Checks the route that each dashboard gets to serve the contents of its widgets.
 */
class WidgetRouteTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    /**
     * @param class-string $dashboardFqcn
     */
    #[TestWith(['admin', '/admin/_widget/{widgetName}', DashboardController::class])]
    #[TestWith(['second_admin', '/second-admin/_widget/{widgetName}', SecondDashboardController::class])]
    #[TestWith(['advanced_admin', '/advanced-admin/_widget/{widgetName}', AdvancedOptionsDashboardController::class])]
    public function testEveryDashboardHasAWidgetRoute(string $dashboardRouteName, string $widgetRoutePath, string $dashboardFqcn): void
    {
        $widgetRoute = $this->getRoute($dashboardRouteName.'_widget');

        $this->assertSame($widgetRoutePath, $widgetRoute->getPath());
        $this->assertSame(['GET'], $widgetRoute->getMethods());
        $this->assertSame('[a-zA-Z0-9_-]+', $widgetRoute->getRequirement('widgetName'));
        $this->assertSame($dashboardFqcn.'::renderWidget', $widgetRoute->getDefault('_controller'));
        $this->assertTrue($widgetRoute->getDefault(EA::ROUTE_CREATED_BY_EASYADMIN));
        $this->assertSame($dashboardFqcn, $widgetRoute->getDefault(EA::DASHBOARD_CONTROLLER_FQCN));
        $this->assertNull($widgetRoute->getDefault(EA::CRUD_CONTROLLER_FQCN));
        $this->assertNull($widgetRoute->getDefault(EA::CRUD_ACTION));
        $this->assertSame($dashboardFqcn.'::index', $this->getRoute($dashboardRouteName)->getDefault('_controller'));
    }

    public function testWidgetRouteHasTheSameOptionsAsItsDashboardRoute(): void
    {
        $dashboardRoute = $this->getRoute('advanced_admin');
        $widgetRoute = $this->getRoute('advanced_admin_widget');

        $this->assertSame('admin.example.com', $widgetRoute->getHost());
        $this->assertSame($dashboardRoute->getHost(), $widgetRoute->getHost());
        $this->assertSame(['https'], $widgetRoute->getSchemes());
        $this->assertSame($dashboardRoute->getCondition(), $widgetRoute->getCondition());
        $this->assertSame(['foo' => '.*', 'widgetName' => '[a-zA-Z0-9_-]+'], $widgetRoute->getRequirements());
        $this->assertSame('Symfony\Component\Routing\RouteCompiler', $widgetRoute->getOption('compiler_class'));
        $this->assertTrue($widgetRoute->getOption('utf8'));

        $defaults = $widgetRoute->getDefaults();
        $this->assertSame('bar', $defaults['foo']);
        $this->assertSame('en', $defaults['_locale']);
        $this->assertSame('html', $defaults['_format']);
        $this->assertTrue($defaults['_stateless']);
    }

    public function testDashboardRouteIsUsedToGenerateTheDashboardUrls(): void
    {
        static::createClient();
        $adminRouteGenerator = static::getContainer()->get(AdminRouteGenerator::class);

        $this->assertSame('admin', $adminRouteGenerator->findRouteName(DashboardController::class));
        $this->assertSame('second_admin', $adminRouteGenerator->findRouteName(SecondDashboardController::class));
        $this->assertSame('advanced_admin', $adminRouteGenerator->findRouteName(AdvancedOptionsDashboardController::class));
        $this->assertEqualsCanonicalizing([
            DashboardController::class => 'admin',
            SecondDashboardController::class => 'second_admin',
            AdvancedOptionsDashboardController::class => 'advanced_admin',
        ], array_intersect_key($adminRouteGenerator->getDashboardRoutes(), [DashboardController::class => true, SecondDashboardController::class => true, AdvancedOptionsDashboardController::class => true]));
    }

    public function testWidgetRouteMatchesOnlyTheHostOfItsDashboard(): void
    {
        $client = static::createClient();

        $client->request('GET', 'https://admin.example.com/advanced-admin/_widget/missing-widget');
        // the route matches, so the dashboard answers that it has no such widget
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('dashboard has no widget called', (string) $client->getResponse()->getContent());

        $client->request('GET', 'https://other.example.com/advanced-admin/_widget/missing-widget');
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('No route found for &quot;GET https://other.example.com/advanced-admin/_widget/missing-widget&quot;', (string) $client->getResponse()->getContent());
    }

    private function getRoute(string $name): Route
    {
        static::createClient();
        $route = static::getContainer()->get('router')->getRouteCollection()->get($name);
        $this->assertNotNull($route, sprintf('The "%s" route must exist.', $name));
        self::ensureKernelShutdown();

        return $route;
    }
}
