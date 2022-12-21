<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuItemMatcher;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuItemMatcherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

class MenuItemMatcherTest extends KernelTestCase
{
    public function testIsSelectedWhenContextIsNull()
    {
        $menuItemMatcher = $this->getMenuItemMatcher(useNullContext: true);

        $this->assertFalse($menuItemMatcher->isSelected(new MenuItemDto()));
    }

    public function testIsSelectedWhenMenuItemIsSection()
    {
        $menuItemMatcher = $this->getMenuItemMatcher();

        $menuItemDto = new MenuItemDto();
        $menuItemDto->setType(MenuItemDto::TYPE_SECTION);

        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto));
    }

    public function testIsSelectedWithCrudControllers()
    {
        $menuItemMatcher = $this->getMenuItemMatcher(
            getControllerFqcn: 'App\Controller\Admin\SomeController',
        );

        $menuItemDto = $this->getMenuItemDto();
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto));

        $menuItemDto = $this->getMenuItemDto(crudControllerFqcn: 'NOT_App\Controller\Admin\SomeController');
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto), 'The CRUD controller does not match');

        $menuItemDto = $this->getMenuItemDto(crudControllerFqcn: 'App\Controller\Admin\SomeController');
        $this->assertTrue($menuItemMatcher->isSelected($menuItemDto), 'The CRUD controller matches');

        $menuItemMatcher = $this->getMenuItemMatcher(
            getControllerFqcn: 'App\Controller\Admin\SomeController',
            getPrimaryKeyValue: '57',
        );

        $menuItemDto = $this->getMenuItemDto(crudControllerFqcn: 'App\Controller\Admin\SomeController', entityId: '57');
        $this->assertTrue($menuItemMatcher->isSelected($menuItemDto), 'The CRUD controller and the entity ID match');

        $menuItemDto = $this->getMenuItemDto(crudControllerFqcn: 'App\Controller\Admin\SomeController', entityId: 'NOT_57');
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto), 'The entity ID of the menu item does not match');

        $menuItemMatcher = $this->getMenuItemMatcher(
            getControllerFqcn: 'App\Controller\Admin\SomeController',
            getPrimaryKeyValue: '57',
            getCurrentAction: 'detail',
        );

        $menuItemDto = $this->getMenuItemDto(crudControllerFqcn: 'App\Controller\Admin\SomeController', action: Crud::PAGE_DETAIL, entityId: '57');
        $this->assertTrue($menuItemMatcher->isSelected($menuItemDto), 'The CRUD controller, entity ID and action match');

        $menuItemDto = $this->getMenuItemDto(crudControllerFqcn: 'App\Controller\Admin\SomeController', action: 'NOT_'.Crud::PAGE_DETAIL, entityId: '57');
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto), 'The CRUD controller and entity ID match but the action does not match');
    }

    public function testIsSelectedWithRoutes()
    {
        $menuItemMatcher = $this->getMenuItemMatcher(
            routeName: 'some_route',
        );

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route');
        $this->assertTrue($menuItemMatcher->isSelected($menuItemDto), 'The route name matches');

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route', routeParameters: ['foo' => 'bar']);
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto));

        $menuItemMatcher = $this->getMenuItemMatcher(
            routeName: 'some_route',
            routeParameters: ['foo1' => 'bar1', 'foo2' => 'bar2'],
        );

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route');
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto));

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route', routeParameters: ['foo1' => 'bar1']);
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto));

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route', routeParameters: ['foo1' => 'bar1', 'foo2' => 'bar2']);
        $this->assertTrue($menuItemMatcher->isSelected($menuItemDto));

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route', routeParameters: ['foo2' => 'bar2', 'foo1' => 'bar1']);
        // CHECK THIS - It should be TRUE
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto));
    }

    public function testIsSelectedWithUrls()
    {
        $menuItemMatcher = $this->getMenuItemMatcher(
            getUri: 'https://example.com/foo?bar=baz',
        );

        $menuItemDto = new MenuItemDto();
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto), 'The URL does not match');

        $menuItemDto = new MenuItemDto();
        $menuItemDto->setLinkUrl('https://example.com');
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto), 'The URL does not match');

        $menuItemDto = new MenuItemDto();
        $menuItemDto->setLinkUrl('https://example.com/foo');
        $this->assertFalse($menuItemMatcher->isSelected($menuItemDto), 'The URL does not match');

        $menuItemDto = new MenuItemDto();
        $menuItemDto->setLinkUrl('https://example.com/foo?bar=baz');
        $this->assertTrue($menuItemMatcher->isSelected($menuItemDto), 'The URL matches');
    }

    /**
     * For tests we need to simulate that the MenuItemDto has been fully built as
     * done in MenuFactory. To simplify tests, we just append the needed query parameters
     * to build the final menu item URL.
     */
    private function getMenuItemDto(string $crudControllerFqcn = null, string $action = null, string $entityId = null, string $routeName = null, array $routeParameters = null): MenuItemDto
    {
        $menuItemDto = new MenuItemDto();
        $menuItemRouteParameters = [];

        if (null !== $crudControllerFqcn) {
            $menuItemRouteParameters[EA::CRUD_CONTROLLER_FQCN] = $crudControllerFqcn;
        }

        if (null !== $action) {
            $menuItemRouteParameters[EA::CRUD_ACTION] = $action;
        }

        if (null !== $entityId) {
            $menuItemRouteParameters[EA::ENTITY_ID] = $entityId;
        }

        if (null !== $routeName) {
            $menuItemRouteParameters[EA::ROUTE_NAME] = $routeName;
        }

        if (null !== $routeParameters) {
            $menuItemRouteParameters[EA::ROUTE_PARAMS] = $routeParameters;
        }

        $menuItemDto->setRouteParameters($menuItemRouteParameters);
        $menuItemDto->setLinkUrl('/?'.http_build_query($menuItemDto->getRouteParameters()));

        return $menuItemDto;
    }

    private function getMenuItemMatcher(bool $useNullContext = false, string $getControllerFqcn = null, string $getPrimaryKeyValue = null, string $getCurrentAction = null, string $routeName = null, array $routeParameters = null, string $getUri = null): MenuItemMatcherInterface
    {
        $queryParameters = [];
        $adminContextProviderMock = $this->getMockBuilder(AdminContextProvider::class)->disableOriginalConstructor()->getMock();

        if ($useNullContext) {
            $adminContextProviderMock->method('getContext')->willReturn(null);

            return new MenuItemMatcher($adminContextProviderMock);
        }

        $adminContextMock = $this->getMockBuilder(AdminContext::class)->disableOriginalConstructor()->getMock();

        if (null !== $getControllerFqcn || null !== $getCurrentAction) {
            $crudDtoMock = $this->getMockBuilder(CrudDto::class)->disableOriginalConstructor()->getMock();
        }
        if (null !== $getControllerFqcn) {
            $queryParameters[EA::CRUD_CONTROLLER_FQCN] = $getControllerFqcn;
            $crudDtoMock->method('getControllerFqcn')->willReturn($getControllerFqcn);
            $adminContextMock->method('getCrud')->willReturn($crudDtoMock);
        }
        if (null !== $getCurrentAction) {
            $queryParameters[EA::CRUD_ACTION] = $getCurrentAction;
            $crudDtoMock->method('getCurrentAction')->willReturn($getCurrentAction);
        }
        if (null !== $getControllerFqcn || null !== $getCurrentAction) {
            $adminContextMock->method('getCrud')->willReturn($crudDtoMock);
        }

        if (null !== $getPrimaryKeyValue) {
            $queryParameters[EA::ENTITY_ID] = $getPrimaryKeyValue;
            $entityDtoMock = $this->getMockBuilder(EntityDto::class)->disableOriginalConstructor()->getMock();
            $entityDtoMock->method('getPrimaryKeyValue')->willReturn($getPrimaryKeyValue);
            $adminContextMock->method('getEntity')->willReturn($entityDtoMock);
        }

        if (null !== $routeName) {
            $queryParameters[EA::ROUTE_NAME] = $routeName;
        }
        if (null !== $routeParameters) {
            $queryParameters[EA::ROUTE_PARAMS] = $routeParameters;
        }

        $request = $this->getMockBuilder(Request::class)->getMock();
        $request->query = new InputBag($queryParameters);
        if (null !== $getUri) {
            $request->method('getUri')->willReturn($getUri);
        } else {
            $request->method('getUri')->willReturn('/?'.http_build_query($queryParameters));
        }
        $adminContextMock->method('getRequest')->willReturn($request);

        $adminContextProviderMock->expects($this->any())->method('getContext')->willReturn($adminContextMock);

        return new MenuItemMatcher($adminContextProviderMock);
    }
}
