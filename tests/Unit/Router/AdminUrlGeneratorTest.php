<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\DashboardContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Router\AdminRouteGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\AdminControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouteCollection;

class AdminUrlGeneratorTest extends KernelTestCase
{
    use ExpectDeprecationTrait;

    public function testGenerateEmptyUrl(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        // the foo=bar query params come from the current request (defined in the mock of the setUp() method)
        $this->assertSame('http://localhost/admin?foo=bar', $adminUrlGenerator->generateUrl());
    }

    public function testGetRouteParameters(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $this->assertSame('bar', $adminUrlGenerator->get('foo'));
        $this->assertNull($adminUrlGenerator->get('this_query_param_does_not_exist'));
    }

    public function testSetRouteParameters(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->set('foo', 'not_bar');
        $this->assertSame('http://localhost/admin?foo=not_bar', $adminUrlGenerator->generateUrl());
    }

    public function testNullParameters(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->set('param1', null);
        $adminUrlGenerator->set('param2', 'null');
        $this->assertSame('http://localhost/admin?foo=bar&param2=null', $adminUrlGenerator->generateUrl());
    }

    public function testSetAll(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setAll(['foo1' => 'bar1', 'foo2' => 'bar2']);
        $this->assertSame('http://localhost/admin?foo=bar&foo1=bar1&foo2=bar2', $adminUrlGenerator->generateUrl());
    }

    public function testUnsetAll(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->set('foo1', 'bar1');
        $adminUrlGenerator->unsetAll();
        $this->assertSame('http://localhost/admin', $adminUrlGenerator->generateUrl());
    }

    public function testUnsetAllExcept(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setAll(['foo1' => 'bar1', 'foo2' => 'bar2', 'foo3' => 'bar3', 'foo4' => 'bar4']);
        $adminUrlGenerator->unsetAllExcept('foo3', 'foo2');
        $this->assertSame('http://localhost/admin?foo2=bar2&foo3=bar3', $adminUrlGenerator->generateUrl());
    }

    public function testParametersAreSorted(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setAll(['1_foo' => 'bar', 'a_foo' => 'bar', '2_foo' => 'bar']);
        $this->assertSame('http://localhost/admin?1_foo=bar&2_foo=bar&a_foo=bar&foo=bar', $adminUrlGenerator->generateUrl());

        $adminUrlGenerator->setAll(['2_foo' => 'bar', 'a_foo' => 'bar', '1_foo' => 'bar']);
        $this->assertSame('http://localhost/admin?1_foo=bar&2_foo=bar&a_foo=bar&foo=bar', $adminUrlGenerator->generateUrl());

        $adminUrlGenerator->setAll(['a_foo' => 'bar', '2_foo' => 'bar', '1_foo' => 'bar']);
        $this->assertSame('http://localhost/admin?1_foo=bar&2_foo=bar&a_foo=bar&foo=bar', $adminUrlGenerator->generateUrl());
    }

    public function testUrlParametersDontAffectOtherUrls(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->set('page', '1');
        $adminUrlGenerator->set('sort', ['id' => 'ASC']);
        $this->assertSame('http://localhost/admin?foo=bar&page=1&sort%5Bid%5D=ASC', $adminUrlGenerator->generateUrl());

        $this->assertSame('http://localhost/admin?foo=bar', $adminUrlGenerator->generateUrl());

        $adminUrlGenerator->set('page', '2');
        $this->assertSame('http://localhost/admin?foo=bar&page=2', $adminUrlGenerator->generateUrl());
        $this->assertNull($adminUrlGenerator->get('sort'));
    }

    public function testExplicitDashboardController(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setDashboard('App\Controller\Admin\CustomHtmlAttributeDashboardController');
        $this->assertSame('http://localhost/custom_html_attribute_admin?foo=bar', $adminUrlGenerator->generateUrl());
    }

    public function testUnknownExplicitDashboardController(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given "ThisDashboardControllerDoesNotExist" class is not a valid Dashboard controller. Make sure it extends from "EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController" or implements "EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface".');

        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setDashboard('ThisDashboardControllerDoesNotExist');
        $adminUrlGenerator->generateUrl();
    }

    public function testDefaultCrudAction(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setController('FooController');
        $this->assertSame('http://localhost/admin?crudAction=index&crudControllerFqcn=FooController&foo=bar', $adminUrlGenerator->generateUrl());

        $adminUrlGenerator->setController('FooController');
        $adminUrlGenerator->setAction(Action::NEW);
        $this->assertSame('http://localhost/admin?crudAction=new&crudControllerFqcn=FooController&foo=bar', $adminUrlGenerator->generateUrl());
    }

    public function testControllerParameterRemovesRouteParameters(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $this->assertNull($adminUrlGenerator->get(EA::ROUTE_NAME));
        $this->assertNull($adminUrlGenerator->get(EA::ROUTE_PARAMS));

        $adminUrlGenerator->setRoute('some_route', ['key' => 'value']);
        $adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $this->assertNull($adminUrlGenerator->get(EA::ROUTE_NAME));
        $this->assertNull($adminUrlGenerator->get(EA::ROUTE_PARAMS));
    }

    public function testActionParameterRemovesRouteParameters(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setAction(Action::INDEX);
        $this->assertNull($adminUrlGenerator->get(EA::ROUTE_NAME));
        $this->assertNull($adminUrlGenerator->get(EA::ROUTE_PARAMS));

        $adminUrlGenerator->setRoute('some_route', ['key' => 'value']);
        $adminUrlGenerator->setAction(Action::INDEX);
        $this->assertNull($adminUrlGenerator->get(EA::ROUTE_NAME));
        $this->assertNull($adminUrlGenerator->get(EA::ROUTE_PARAMS));
    }

    public function testRouteParametersRemoveOtherParameters(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setRoute('some_route', ['key' => 'value']);
        $this->assertNull($adminUrlGenerator->get(EA::CRUD_CONTROLLER_FQCN));

        $adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $adminUrlGenerator->set('foo', 'bar');
        $adminUrlGenerator->setRoute('some_route', ['key' => 'value']);

        $this->assertNull($adminUrlGenerator->get(EA::CRUD_CONTROLLER_FQCN));
        $this->assertNull($adminUrlGenerator->get('foo'));
    }

    public function testLegacyParameters(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();
        $adminUrlGenerator->set(EA::MENU_INDEX, 3);

        $this->assertSame(3, $adminUrlGenerator->get(EA::MENU_INDEX));
    }

    /**
     * @group legacy
     */
    public function testDeprecatedParameterMessage(): void
    {
        $this->expectDeprecation('Since easycorp/easyadmin-bundle 4.5.0: Using the "menuIndex" query parameter is deprecated. Menu items are now highlighted automatically based on the Request data, so you don\'t have to deal with menu items manually anymore.');

        $adminUrlGenerator = $this->getAdminUrlGenerator();
        $adminUrlGenerator->set('menuIndex', 1);
    }

    /**
     * @group legacy
     */
    public function testIncludeReferrer(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->includeReferrer();
        $this->assertSame('http://localhost/admin?foo=bar&referrer=/?foo%3Dbar', $adminUrlGenerator->generateUrl());
    }

    /**
     * @group legacy
     */
    public function testRemoveReferrer(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->removeReferrer();
        $this->assertSame('http://localhost/admin?foo=bar', $adminUrlGenerator->generateUrl());

        $adminUrlGenerator->setReferrer('https://example.com/foo');
        $adminUrlGenerator->removeReferrer();
        $this->assertSame('http://localhost/admin?foo=bar', $adminUrlGenerator->generateUrl());
    }

    public function testNoReferrerByDefault(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $this->assertStringNotContainsString('referrer', $adminUrlGenerator->generateUrl());
    }

    /**
     * @group legacy
     */
    public function testCustomReferrer(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->setReferrer('any_custom_value');
        $this->assertSame('http://localhost/admin?foo=bar&referrer=any_custom_value', $adminUrlGenerator->generateUrl());
    }

    /**
     * @group legacy
     */
    public function testPersistentCustomReferrer(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();
        $adminUrlGenerator->setReferrer('any_custom_value');
        $this->assertSame('http://localhost/admin?foo=bar&referrer=any_custom_value', $adminUrlGenerator->generateUrl());

        // test that the custom referrer value does not persist after generating the URL
        $adminUrlGenerator->includeReferrer();
        $this->assertSame('http://localhost/admin?foo=bar&referrer=/?foo%3Dbar', $adminUrlGenerator->generateUrl());
    }

    public function testRelativeUrls(): void
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        $adminUrlGenerator->set('foo1', 'bar1');
        $adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $this->assertSame('http://localhost/admin?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CSomeCrudController&foo=bar&foo1=bar1', $adminUrlGenerator->generateUrl());
    }

    private function getAdminUrlGenerator(bool $absoluteUrls = true): AdminUrlGeneratorInterface
    {
        self::bootKernel();

        $dashboardDto = Dashboard::new()->getAsDto();
        $dashboardDto->setRouteName('admin');
        $dashboardDto->setAbsoluteUrls($absoluteUrls);

        $adminContext = AdminContext::forTesting(
            requestContext: RequestContext::forTesting(new Request(['foo' => 'bar'])),
            dashboardContext: DashboardContext::forTesting($dashboardDto),
        );

        $request = new Request(query: ['foo' => 'bar'], attributes: [EA::CONTEXT_REQUEST_ATTRIBUTE => $adminContext]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $adminContextProvider = new AdminContextProvider($requestStack);

        // create a minimal temp directory and cache file for testing
        $tempDir = sys_get_temp_dir().'/easyadmin_test_url_gen_'.uniqid();
        @mkdir($tempDir.'/easyadmin', 0777, true);
        $cacheContent = '<?php return '.var_export([
            'admin' => 'App\Controller\Admin\DashboardController::index',
            'custom_html_attribute_admin' => 'App\Controller\Admin\CustomHtmlAttributeDashboardController::index',
        ], true).';';
        file_put_contents($tempDir.'/easyadmin/routes-dashboard.php', $cacheContent);

        $adminControllers = new AdminControllerRegistry(
            $tempDir,
            [], // crudFqcnToEntityFqcnMap
            ['App\Controller\Admin\DashboardController', 'App\Controller\Admin\CustomHtmlAttributeDashboardController']
        );

        $router = self::getContainer()->get('router');

        $adminRouteGenerator = $this->getMockBuilder(AdminRouteGeneratorInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['usesPrettyUrls'])
            ->getMockForAbstractClass();
        $adminRouteGenerator->method('generateAll')->willReturn(new RouteCollection());
        $adminRouteGenerator->method('findRouteName')->willReturn(null);
        $adminRouteGenerator->method('usesPrettyUrls')->willReturn(true);

        $cacheItem = new CacheItem();
        $cacheItem->set([]);
        $cacheMock = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $cacheMock->method('getItem')->willReturn($cacheItem);
        $cacheMock->method('save')->willReturn(true);

        return new AdminUrlGenerator($adminContextProvider, $router, $adminControllers, $adminRouteGenerator, $cacheMock);
    }
}
