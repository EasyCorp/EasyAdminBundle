<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Menu\MenuItemMatcher;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminRouteGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\ActionsCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\BlogPostCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\FormFieldsetsCrudController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class MenuItemMatcherTest extends KernelTestCase
{
    public function testIsSelectedWhenContextIsNull(): void
    {
        $request = $this->createRequest();

        self::bootKernel();
        $adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
        $adminRouteGenerator = self::getContainer()->get(AdminRouteGenerator::class);
        $menuItemMatcher = new MenuItemMatcher($adminUrlGenerator, $adminRouteGenerator);
        $menuItemDto = new MenuItemDto();

        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);

        $this->assertFalse($menuItemDto->isSelected());
    }

    public function testIsSelectedWhenMenuItemIsSection(): void
    {
        $request = $this->createRequest();

        self::bootKernel();
        $adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
        $adminRouteGenerator = self::getContainer()->get(AdminRouteGenerator::class);
        $menuItemMatcher = new MenuItemMatcher($adminUrlGenerator, $adminRouteGenerator);
        $menuItemDto = new MenuItemDto();
        $menuItemDto->setType(MenuItemDto::TYPE_SECTION);

        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);

        $this->assertFalse($menuItemDto->isSelected());
    }

    public function testIsSelectedWithCrudControllers(): void
    {
        self::bootKernel();
        $adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
        $adminRouteGenerator = self::getContainer()->get(AdminRouteGenerator::class);
        $menuItemMatcher = new MenuItemMatcher($adminUrlGenerator, $adminRouteGenerator);

        // with pretty URLs, we need to use actual generated URLs for matching
        // generate the category index URL to use as the request path
        $categoryIndexUrl = $adminUrlGenerator->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Crud::PAGE_INDEX)
            ->generateUrl();
        $categoryIndexPath = parse_url($categoryIndexUrl, \PHP_URL_PATH);

        $request = $this->createRequest(
            crudControllerFqcn: CategoryCrudController::class,
            requestPath: $categoryIndexPath,
        );

        $menuItemDto = $this->getMenuItemDto();
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);

        $this->assertFalse($menuItemDto->isSelected());

        $menuItemDto = $this->getMenuItemDtoWithUrl($adminUrlGenerator, BlogPostCrudController::class);
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertFalse($menuItemDto->isSelected(), 'The CRUD controller does not match');

        $menuItemDto = $this->getMenuItemDtoWithUrl($adminUrlGenerator, CategoryCrudController::class);
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertTrue($menuItemDto->isSelected(), 'The CRUD controller matches');

        // test edit action with entityId
        $categoryEditUrl = $adminUrlGenerator->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Crud::PAGE_EDIT)
            ->setEntityId('57')
            ->generateUrl();
        $categoryEditPath = parse_url($categoryEditUrl, \PHP_URL_PATH);

        $request = $this->createRequest(
            crudControllerFqcn: CategoryCrudController::class,
            entityId: '57',
            action: 'edit',
            requestPath: $categoryEditPath,
        );

        $menuItemDto = $this->getMenuItemDtoWithUrl($adminUrlGenerator, CategoryCrudController::class, Crud::PAGE_EDIT, '57');
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertTrue($menuItemDto->isSelected(), 'The CRUD controller and the entity ID match');

        $menuItemDto = $this->getMenuItemDtoWithUrl($adminUrlGenerator, CategoryCrudController::class, Crud::PAGE_EDIT, 'NOT_57');
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertFalse($menuItemDto->isSelected(), 'The entity ID of the menu item does not match');

        // test detail action with entityId
        $categoryDetailUrl = $adminUrlGenerator->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController(CategoryCrudController::class)
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId('57')
            ->generateUrl();
        $categoryDetailPath = parse_url($categoryDetailUrl, \PHP_URL_PATH);

        $request = $this->createRequest(
            crudControllerFqcn: CategoryCrudController::class,
            entityId: '57',
            action: 'detail',
            requestPath: $categoryDetailPath,
        );

        $menuItemDto = $this->getMenuItemDtoWithUrl($adminUrlGenerator, CategoryCrudController::class, Crud::PAGE_DETAIL, '57');
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertTrue($menuItemDto->isSelected(), 'The CRUD controller, entity ID and action match');

        $menuItemDto = $this->getMenuItemDtoWithUrl($adminUrlGenerator, CategoryCrudController::class, 'NOT_'.Crud::PAGE_DETAIL, '57');
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertFalse($menuItemDto->isSelected(), 'The CRUD controller and entity ID match but the action does not match');
    }

    private function getMenuItemDtoWithUrl(AdminUrlGenerator $adminUrlGenerator, string $controllerFqcn, string $action = Crud::PAGE_INDEX, ?string $entityId = null): MenuItemDto
    {
        $menuItemDto = new MenuItemDto();

        $adminUrlGenerator->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController($controllerFqcn)
            ->setAction($action);

        if (null !== $entityId) {
            $adminUrlGenerator->setEntityId($entityId);
        }

        $url = $adminUrlGenerator->generateUrl();
        $menuItemDto->setLinkUrl($url);

        return $menuItemDto;
    }

    public function testIsSelectedWithRoutes(): void
    {
        $request = $this->createRequest(
            routeName: 'some_route',
        );

        self::bootKernel();
        $adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
        $adminRouteGenerator = self::getContainer()->get(AdminRouteGenerator::class);
        $menuItemMatcher = new MenuItemMatcher($adminUrlGenerator, $adminRouteGenerator);
        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route');

        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);

        $this->assertTrue($menuItemDto->isSelected(), 'The route name matches');

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route', routeParameters: ['foo' => 'bar']);
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertFalse($menuItemDto->isSelected());

        $request = $this->createRequest(
            routeName: 'some_route',
            routeParameters: ['foo1' => 'bar1', 'foo2' => 'bar2'],
        );

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route');
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertFalse($menuItemDto->isSelected());

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route', routeParameters: ['foo1' => 'bar1']);
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertFalse($menuItemDto->isSelected());

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route', routeParameters: ['foo1' => 'bar1', 'foo2' => 'bar2']);
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertTrue($menuItemDto->isSelected());

        $menuItemDto = $this->getMenuItemDto(routeName: 'some_route', routeParameters: ['foo2' => 'bar2', 'foo1' => 'bar1']);
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertTrue($menuItemDto->isSelected(), 'A menu item with the same query parameters but in different order matches too.');
    }

    public function testIsSelectedWithUrls(): void
    {
        $request = $this->createRequest(
            requestPath: '/foo',
            queryParameters: ['bar' => 'baz'],
        );

        self::bootKernel();
        $adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
        $adminRouteGenerator = self::getContainer()->get(AdminRouteGenerator::class);
        $menuItemMatcher = new MenuItemMatcher($adminUrlGenerator, $adminRouteGenerator);
        $menuItemDto = new MenuItemDto();

        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);

        $this->assertFalse($menuItemDto->isSelected(), 'The URL does not match');

        $menuItemDto = new MenuItemDto();
        $menuItemDto->setLinkUrl('https://example.com');
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertFalse($menuItemDto->isSelected(), 'The URL does not match');

        $menuItemDto = new MenuItemDto();
        $menuItemDto->setLinkUrl('https://example.com/foo');
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertFalse($menuItemDto->isSelected(), 'The URL does not match');

        $menuItemDto = new MenuItemDto();
        $menuItemDto->setLinkUrl('https://example.com/foo?bar=baz');
        $menuItemMatcher->markSelectedMenuItem([$menuItemDto], $request);
        $this->assertTrue($menuItemDto->isSelected(), 'The URL matches');
    }

    public function testMenuWithDashboardItem(): void
    {
        $dashboardMenuItem = new MenuItemDto();
        $dashboardMenuItem->setLabel('item1');
        $dashboardMenuItem->setType(MenuItemDto::TYPE_DASHBOARD);

        $menuItems = [
            $dashboardMenuItem,
            $this->getMenuItemDto(label: 'item2', routeName: 'item2'),
        ];

        $request = $this->createRequest(
            routeName: 'item2',
        );

        self::bootKernel();
        $adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
        $adminRouteGenerator = self::getContainer()->get(AdminRouteGenerator::class);
        $menuItemMatcher = new MenuItemMatcher($adminUrlGenerator, $adminRouteGenerator);
        $menuItems = $menuItemMatcher->markSelectedMenuItem($menuItems, $request);

        $this->assertSame('item2', $this->getSelectedMenuItemLabel($menuItems), 'Perfect match: Dashboard item');
    }

    public function testComplexMenu(): void
    {
        self::bootKernel();
        $adminUrlGenerator = self::getContainer()->get(AdminUrlGenerator::class);
        $adminRouteGenerator = self::getContainer()->get(AdminRouteGenerator::class);
        $menuItemMatcher = new MenuItemMatcher($adminUrlGenerator, $adminRouteGenerator);

        // generate proper pretty URL paths for the requests
        $categoryIndexPath = parse_url($adminUrlGenerator->unsetAll()->setDashboard(DashboardController::class)->setController(CategoryCrudController::class)->setAction(Crud::PAGE_INDEX)->generateUrl(), \PHP_URL_PATH);
        $blogPostNewPath = parse_url($adminUrlGenerator->unsetAll()->setDashboard(DashboardController::class)->setController(BlogPostCrudController::class)->setAction(Crud::PAGE_NEW)->generateUrl(), \PHP_URL_PATH);

        // test 1: Perfect match with INDEX action
        $menuItems = $this->getComplexMenuItemsWithPrettyUrls($adminUrlGenerator);
        $request = $this->createRequest(
            crudControllerFqcn: CategoryCrudController::class,
            requestPath: $categoryIndexPath,
        );

        $menuItems = $menuItemMatcher->markSelectedMenuItem($menuItems, $request);

        $this->assertSame('item1', $this->getSelectedMenuItemLabel($menuItems), 'Perfect match: CRUD controller and action');
        $this->assertNull($this->getExpandedMenuItemLabel($menuItems), 'No menu item is marked as expanded');

        // test 2: Approximate match - controller matches, action doesn't match
        unset($menuItems);
        $menuItems = $this->getComplexMenuItemsWithPrettyUrls($adminUrlGenerator);
        $request = $this->createRequest(
            crudControllerFqcn: BlogPostCrudController::class,
            action: 'new',
            requestPath: $blogPostNewPath,
        );
        $menuItems = $menuItemMatcher->markSelectedMenuItem($menuItems, $request);
        $this->assertSame('item3', $this->getSelectedMenuItemLabel($menuItems), 'Approximate match: controller matches, action doesn\'t match; the item with the INDEX action is selected by default');
        $this->assertNull($this->getExpandedMenuItemLabel($menuItems), 'No menu item is marked as expanded');
    }

    private function getComplexMenuItemsWithPrettyUrls(AdminUrlGenerator $adminUrlGenerator): array
    {
        $item1 = $this->getMenuItemDtoWithUrl($adminUrlGenerator, CategoryCrudController::class);
        $item1->setLabel('item1');

        // item2 is removed because EDIT action requires entityId with pretty URLs

        $item3 = $this->getMenuItemDtoWithUrl($adminUrlGenerator, BlogPostCrudController::class);
        $item3->setLabel('item3');

        $item5 = $this->getMenuItemDtoWithUrl($adminUrlGenerator, CategoryCrudController::class, Crud::PAGE_NEW);
        $item5->setLabel('item5');

        $item6 = $this->getMenuItemDtoWithUrl($adminUrlGenerator, BlogPostCrudController::class, Crud::PAGE_EDIT, '57');
        $item6->setLabel('item6');

        $item7 = $this->getMenuItemDtoWithUrl($adminUrlGenerator, ActionsCrudController::class);
        $item7->setLabel('item7');

        $item8 = $this->getMenuItemDtoWithUrl($adminUrlGenerator, CategoryCrudController::class);
        $item8->setLabel('item8');

        $item4 = new MenuItemDto();
        $item4->setLabel('item4');
        $item4->setSubItems([$item5, $item6, $item7, $item8]);

        $item9 = $this->getMenuItemDtoWithUrl($adminUrlGenerator, FormFieldsetsCrudController::class);
        $item9->setLabel('item9');

        return [$item1, $item3, $item4, $item9];
    }

    private function getSelectedMenuItemLabel(array $menuItems): ?string
    {
        foreach ($menuItems as $menuItemDto) {
            if ($menuItemDto->isSelected()) {
                return $menuItemDto->getLabel();
            }

            if (null !== $subItems = $menuItemDto->getSubItems()) {
                if (null !== $label = $this->getSelectedMenuItemLabel($subItems)) {
                    return $label;
                }
            }
        }

        return null;
    }

    private function getExpandedMenuItemLabel(array $menuItems): ?string
    {
        foreach ($menuItems as $menuItemDto) {
            if ($menuItemDto->isExpanded()) {
                return $menuItemDto->getLabel();
            }
        }

        return null;
    }

    /**
     * For tests we need to simulate that the MenuItemDto has been fully built as
     * done in MenuFactory. To simplify tests, we just append the needed query parameters
     * to build the final menu item URL.
     */
    private function getMenuItemDto(?string $crudControllerFqcn = null, ?string $action = null, ?string $entityId = null, ?string $routeName = null, ?array $routeParameters = null, ?array $subItems = null, ?string $label = null): MenuItemDto
    {
        $menuItemDto = new MenuItemDto();
        $menuItemRouteParameters = [];

        if (null !== $label) {
            $menuItemDto->setLabel($label);
        }

        if (null !== $crudControllerFqcn) {
            $menuItemRouteParameters[EA::CRUD_CONTROLLER_FQCN] = $crudControllerFqcn;
        }

        if (null !== $action) {
            $menuItemRouteParameters[EA::CRUD_ACTION] = $action;
        } elseif (null === $action && null === $routeName) {
            $menuItemRouteParameters[EA::CRUD_ACTION] = Crud::PAGE_INDEX;
        }

        if (null !== $entityId) {
            $menuItemRouteParameters[EA::ENTITY_ID] = $entityId;
        }

        if (null !== $routeName) {
            $menuItemRouteParameters[EA::ROUTE_NAME] = $routeName;
        }

        if (null !== $routeParameters) {
            // sort route parameters for consistent URL comparison
            ksort($routeParameters);
            $menuItemRouteParameters[EA::ROUTE_PARAMS] = $routeParameters;
        }

        if (null !== $subItems) {
            $menuItemDto->setSubItems($subItems);
        }

        $menuItemDto->setRouteParameters($menuItemRouteParameters);
        // sort parameters for consistent URL matching (as done in MenuItemMatcher)
        $sortedParams = $menuItemDto->getRouteParameters();
        ksort($sortedParams);
        $menuItemDto->setLinkUrl('/?'.http_build_query($sortedParams));

        return $menuItemDto;
    }

    private function createRequest(?string $crudControllerFqcn = null, ?string $entityId = null, ?string $action = null, ?string $routeName = null, ?array $routeParameters = null, ?string $requestPath = null, array $queryParameters = []): Request
    {
        // set up attributes (always used for matching)
        $attributes = [];
        if (null !== $crudControllerFqcn) {
            $attributes[EA::CRUD_CONTROLLER_FQCN] = $crudControllerFqcn;
            $attributes[EA::DASHBOARD_CONTROLLER_FQCN] = DashboardController::class;
        }
        if (null !== $action) {
            $attributes[EA::CRUD_ACTION] = $action;
        }
        if (null !== $entityId) {
            $attributes[EA::ENTITY_ID] = $entityId;
        }
        if (null !== $routeName) {
            $attributes[EA::ROUTE_NAME] = $routeName;
        }
        if (null !== $routeParameters) {
            $attributes[EA::ROUTE_PARAMS] = $routeParameters;
        }

        // for pretty URLs (when requestPath is provided), don't include EasyAdmin params in query string
        // as they're encoded in the URL path. For legacy URLs, include them in query string.
        if (null === $requestPath) {
            $queryParameters[EA::CRUD_CONTROLLER_FQCN] = $crudControllerFqcn;
            $queryParameters[EA::CRUD_ACTION] = $action;
            $queryParameters[EA::ENTITY_ID] = $entityId;
            $queryParameters[EA::ROUTE_NAME] = $routeName;
            $queryParameters[EA::ROUTE_PARAMS] = $routeParameters;
            $queryParameters = array_filter($queryParameters, static fn ($value) => null !== $value);
        }

        $serverParameters = [
            'HTTPS' => 'On',
            'HTTP_HOST' => 'example.com',
            'QUERY_STRING' => http_build_query($queryParameters),
        ];
        if (null !== $requestPath) {
            $serverParameters['REQUEST_URI'] = '/'.ltrim($requestPath, '/');
        }

        return new Request(query: $queryParameters, attributes: $attributes, server: $serverParameters);
    }
}
