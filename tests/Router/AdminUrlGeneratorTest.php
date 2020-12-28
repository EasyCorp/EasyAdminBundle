<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Router;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\DashboardControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\UrlSigner;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class AdminUrlGeneratorTest extends WebTestCase
{
    use ExpectDeprecationTrait;

    private $adminUrlGenerator;
    private $adminUrlGeneratorWithSignedUrls;

    protected function setUp(): void
    {
        self::bootKernel();

        $adminContext = $this->getMockBuilder(AdminContext::class)->disableOriginalConstructor()->getMock();
        $adminContext->method('getDashboardRouteName')->willReturn('admin');
        $adminContext->method('getSignedUrls')->willReturn(false);
        $adminContext->method('getRequest')->willReturn(new Request(['foo' => 'bar']));

        $adminContextProvider = $this->getMockBuilder(AdminContextProvider::class)->disableOriginalConstructor()->getMock();
        $adminContextProvider->method('getContext')->willReturn($adminContext);

        $adminContextWithSignedUrls = $this->getMockBuilder(AdminContext::class)->disableOriginalConstructor()->getMock();
        $adminContextWithSignedUrls->method('getDashboardRouteName')->willReturn('admin');
        $adminContextWithSignedUrls->method('getSignedUrls')->willReturn(true);
        $adminContextWithSignedUrls->method('getRequest')->willReturn(new Request(['foo' => 'bar']));

        $adminContextProviderWithSignedUrls = $this->getMockBuilder(AdminContextProvider::class)->disableOriginalConstructor()->getMock();
        $adminContextProviderWithSignedUrls->method('getContext')->willReturn($adminContextWithSignedUrls);

        $dashboardControllerRegistry = $this->getMockBuilder(DashboardControllerRegistry::class)->disableOriginalConstructor()->getMock();
        $dashboardControllerRegistry->method('getRouteByControllerFqcn')->willReturnMap([
            ['App\Controller\Admin\SomeDashboardController', 'another_admin'],
        ]);
        $dashboardControllerRegistry->method('getNumberOfDashboards')->willReturn(2);
        $dashboardControllerRegistry->method('getFirstDashboardRoute')->willReturn('admin');

        $crudControllerRegistry = $this->getMockBuilder(CrudControllerRegistry::class)->disableOriginalConstructor()->getMock();
        $crudControllerRegistry->method('findCrudFqcnByCrudId')->willReturnMap([
            ['a1b2c3', 'App\Controller\Admin\SomeCrudController'],
        ]);

        $this->adminUrlGenerator = new AdminUrlGenerator(
            $adminContextProvider,
            self::$container->get('router'),
            $dashboardControllerRegistry,
            $crudControllerRegistry,
            new UrlSigner('abc123')
        );

        $this->adminUrlGeneratorWithSignedUrls = new AdminUrlGenerator(
            $adminContextProviderWithSignedUrls,
            self::$container->get('router'),
            $dashboardControllerRegistry,
            $crudControllerRegistry,
            new UrlSigner('abc123')
        );
    }

    public function testGenerateEmptyUrl()
    {
        // the foo=bar query params come from the current request (defined in the mock of the setUp() method)
        $this->assertSame('http://localhost/admin?foo=bar', $this->adminUrlGenerator->generateUrl());
    }

    public function testGetRouteParameters()
    {
        $this->assertSame('bar', $this->adminUrlGenerator->get('foo'));
        $this->assertNull($this->adminUrlGenerator->get('this_query_param_does_not_exist'));
    }

    public function testSetRouteParameters()
    {
        $this->adminUrlGenerator->set('foo', 'not_bar');
        $this->assertSame('http://localhost/admin?foo=not_bar', $this->adminUrlGenerator->generateUrl());
    }

    public function testNullParameters()
    {
        $this->adminUrlGenerator->set('param1', null);
        $this->adminUrlGenerator->set('param2', 'null');
        $this->assertSame('http://localhost/admin?foo=bar&param2=null', $this->adminUrlGenerator->generateUrl());
    }

    public function testSetAll()
    {
        $this->adminUrlGenerator->setAll(['foo1' => 'bar1', 'foo2' => 'bar2']);
        $this->assertSame('http://localhost/admin?foo=bar&foo1=bar1&foo2=bar2', $this->adminUrlGenerator->generateUrl());
    }

    public function testUnsetAll()
    {
        $this->adminUrlGenerator->set('foo1', 'bar1');
        $this->adminUrlGenerator->unsetAll();
        $this->assertSame('http://localhost/admin', $this->adminUrlGenerator->generateUrl());
    }

    public function testUnsetAllExcept()
    {
        $this->adminUrlGenerator->setAll(['foo1' => 'bar1', 'foo2' => 'bar2', 'foo3' => 'bar3', 'foo4' => 'bar4']);
        $this->adminUrlGenerator->unsetAllExcept('foo3', 'foo2');
        $this->assertSame('http://localhost/admin?foo2=bar2&foo3=bar3', $this->adminUrlGenerator->generateUrl());
    }

    public function testParametersAreSorted()
    {
        $this->adminUrlGenerator->setAll(['1_foo' => 'bar', 'a_foo' => 'bar', '2_foo' => 'bar']);
        $this->assertSame('http://localhost/admin?1_foo=bar&2_foo=bar&a_foo=bar&foo=bar', $this->adminUrlGenerator->generateUrl());

        $this->adminUrlGenerator->setAll(['2_foo' => 'bar', 'a_foo' => 'bar', '1_foo' => 'bar']);
        $this->assertSame('http://localhost/admin?1_foo=bar&2_foo=bar&a_foo=bar&foo=bar', $this->adminUrlGenerator->generateUrl());

        $this->adminUrlGenerator->setAll(['a_foo' => 'bar', '2_foo' => 'bar', '1_foo' => 'bar']);
        $this->assertSame('http://localhost/admin?1_foo=bar&2_foo=bar&a_foo=bar&foo=bar', $this->adminUrlGenerator->generateUrl());
    }

    public function testUrlParametersDontAffectOtherUrls()
    {
        $this->adminUrlGenerator->set('page', '1');
        $this->adminUrlGenerator->set('sort', ['id' => 'ASC']);
        $this->assertSame('http://localhost/admin?foo=bar&page=1&sort%5Bid%5D=ASC', $this->adminUrlGenerator->generateUrl());

        $this->assertSame('http://localhost/admin?foo=bar', $this->adminUrlGenerator->generateUrl());

        $this->adminUrlGenerator->set('page', '2');
        $this->assertSame('http://localhost/admin?foo=bar&page=2', $this->adminUrlGenerator->generateUrl());
        $this->assertNull($this->adminUrlGenerator->get('sort'));
    }

    public function testExplicitDashboardController()
    {
        $this->adminUrlGenerator->setDashboard('App\Controller\Admin\SomeDashboardController');
        $this->assertSame('http://localhost/another_admin?foo=bar', $this->adminUrlGenerator->generateUrl());
    }

    public function testUnknownExplicitDashboardController()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given "ThisDashboardControllerDoesNotExist" class is not a valid Dashboard controller. Make sure it extends from "EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController" or implements "EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface".');

        $this->adminUrlGenerator->setDashboard('ThisDashboardControllerDoesNotExist');
        $this->adminUrlGenerator->generateUrl();
    }

    /**
     * @group legacy
     */
    public function testCrudIdMethodIsTransformedIntoCrudFqcn()
    {
        $this->adminUrlGenerator->setCrudId('a1b2c3');
        // The following assert should work, but it fails for some unknown reason ("Failed asserting that string matches format description.")
        // $this->expectDeprecation("Since easycorp/easyadmin-bundle 3.2.0: The \"setCrudId()\" method of the \"EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator\" service and the related \"crudId\" query parameter are deprecated. Instead, use the CRUD Controller FQCN and the \"setController()\" method like this: ->setController('App\Controller\Admin\SomeCrudController').");

        $this->assertNull($this->adminUrlGenerator->get(EA::CRUD_ID));
        $this->assertSame('App\Controller\Admin\SomeCrudController', $this->adminUrlGenerator->get(EA::CRUD_CONTROLLER_FQCN));
    }

    public function testCrudIdParameterIsTransformedIntoCrudFqcn()
    {
        // don't use setCrudId() because it transforms the crudId into crudFqcn automatically
        $this->adminUrlGenerator->set(EA::CRUD_ID, 'a1b2c3');
        $this->assertSame('http://localhost/admin?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CSomeCrudController&foo=bar', $this->adminUrlGenerator->generateUrl());
    }

    public function testUnknowCrudId()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given "this_id_does_not_exist" value is not a valid CRUD ID. Instead of dealing with CRUD controller IDs when generating admin URLs, use the "setController()" method to set the CRUD controller FQCN.');

        // don't use setCrudId() because it transforms the crudId into crudFqcn automatically
        $this->adminUrlGenerator->set(EA::CRUD_ID, 'this_id_does_not_exist');
        $this->adminUrlGenerator->generateUrl();
    }

    public function testDefaultCrudAction()
    {
        $this->adminUrlGenerator->setController('FooController');
        $this->assertSame('http://localhost/admin?crudAction=index&crudControllerFqcn=FooController&foo=bar', $this->adminUrlGenerator->generateUrl());

        $this->adminUrlGenerator->setController('FooController');
        $this->adminUrlGenerator->setAction(Action::NEW);
        $this->assertSame('http://localhost/admin?crudAction=new&crudControllerFqcn=FooController&foo=bar', $this->adminUrlGenerator->generateUrl());
    }

    public function testControllerParameterRemovesRouteParameters()
    {
        $this->adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $this->assertNull($this->adminUrlGenerator->get(EA::ROUTE_NAME));
        $this->assertNull($this->adminUrlGenerator->get(EA::ROUTE_PARAMS));

        $this->adminUrlGenerator->setRoute('some_route', ['key' => 'value']);
        $this->adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $this->assertNull($this->adminUrlGenerator->get(EA::ROUTE_NAME));
        $this->assertNull($this->adminUrlGenerator->get(EA::ROUTE_PARAMS));
    }

    public function testActionParameterRemovesRouteParameters()
    {
        $this->adminUrlGenerator->setAction(Action::INDEX);
        $this->assertNull($this->adminUrlGenerator->get(EA::ROUTE_NAME));
        $this->assertNull($this->adminUrlGenerator->get(EA::ROUTE_PARAMS));

        $this->adminUrlGenerator->setRoute('some_route', ['key' => 'value']);
        $this->adminUrlGenerator->setAction(Action::INDEX);
        $this->assertNull($this->adminUrlGenerator->get(EA::ROUTE_NAME));
        $this->assertNull($this->adminUrlGenerator->get(EA::ROUTE_PARAMS));
    }

    public function testRouteParametersRemoveOtherParameters()
    {
        $this->adminUrlGenerator->setRoute('some_route', ['key' => 'value']);
        $this->assertNull($this->adminUrlGenerator->get(EA::CRUD_CONTROLLER_FQCN));

        $this->adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $this->adminUrlGenerator->set(EA::MENU_INDEX, 3);
        $this->adminUrlGenerator->set('foo', 'bar');
        $this->adminUrlGenerator->setRoute('some_route', ['key' => 'value']);

        $this->assertNull($this->adminUrlGenerator->get(EA::CRUD_CONTROLLER_FQCN));
        $this->assertNull($this->adminUrlGenerator->get('foo'));
        $this->assertSame(3, $this->adminUrlGenerator->get(EA::MENU_INDEX));
    }

    public function testIncludeReferrer()
    {
        $this->adminUrlGenerator->includeReferrer();
        $this->assertSame('http://localhost/admin?foo=bar&referrer=/?foo%3Dbar', $this->adminUrlGenerator->generateUrl());
    }

    public function testRemoveReferrer()
    {
        $this->adminUrlGenerator->removeReferrer();
        $this->assertSame('http://localhost/admin?foo=bar', $this->adminUrlGenerator->generateUrl());

        $this->adminUrlGenerator->set(EA::REFERRER, 'https://example.com/foo');
        $this->adminUrlGenerator->removeReferrer();
        $this->assertSame('http://localhost/admin?foo=bar', $this->adminUrlGenerator->generateUrl());
    }

    public function testUrlsWithSignatures()
    {
        $this->adminUrlGenerator->set('foo1', 'bar1');
        $this->adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $this->adminUrlGenerator->addSignature();
        $this->assertSame('http://localhost/admin?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CSomeCrudController&foo=bar&foo1=bar1&signature=yGwN-pwX_xBtCMu87wfD0wyXQm-v4QO_IjCiB6cMvRw', $this->adminUrlGenerator->generateUrl());

        $this->adminUrlGeneratorWithSignedUrls->set('foo1', 'bar1');
        $this->adminUrlGeneratorWithSignedUrls->setController('App\Controller\Admin\SomeCrudController');
        $this->assertSame('http://localhost/admin?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CSomeCrudController&foo=bar&foo1=bar1&signature=yGwN-pwX_xBtCMu87wfD0wyXQm-v4QO_IjCiB6cMvRw', $this->adminUrlGeneratorWithSignedUrls->generateUrl());
    }

    public function testUrlsWithoutSignatures()
    {
        $this->adminUrlGenerator->set('foo1', 'bar1');
        $this->adminUrlGenerator->setController('App\Controller\Admin\SomeCrudController');
        $this->assertSame('http://localhost/admin?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CSomeCrudController&foo=bar&foo1=bar1', $this->adminUrlGenerator->generateUrl());

        $this->adminUrlGeneratorWithSignedUrls->set('foo1', 'bar1');
        $this->adminUrlGeneratorWithSignedUrls->setController('App\Controller\Admin\SomeCrudController');
        $this->adminUrlGeneratorWithSignedUrls->addSignature(false);
        $this->assertSame('http://localhost/admin?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CSomeCrudController&foo=bar&foo1=bar1', $this->adminUrlGeneratorWithSignedUrls->generateUrl());
    }
}
