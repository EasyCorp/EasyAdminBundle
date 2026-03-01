<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemMatcherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Translation\EntityTranslationIdGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Contracts\Translation\TranslatableInterface;

class MenuFactoryTest extends TestCase
{
    private AdminContextProviderInterface $adminContextProvider;
    private AuthorizationCheckerInterface $authChecker;
    private LogoutUrlGenerator $logoutUrlGenerator;
    private AdminUrlGeneratorInterface $adminUrlGenerator;
    private MenuItemMatcherInterface $menuItemMatcher;
    private MenuFactory $menuFactory;
    private EntityTranslationIdGeneratorInterface $translationIdGenerator;

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->logoutUrlGenerator = $this->createMock(LogoutUrlGenerator::class);
        $this->adminUrlGenerator = $this->createMock(AdminUrlGeneratorInterface::class);
        $this->menuItemMatcher = $this->createMock(MenuItemMatcherInterface::class);
        $this->translationIdGenerator = $this->createMock(EntityTranslationIdGeneratorInterface::class);

        $this->menuFactory = new MenuFactory(
            $this->adminContextProvider,
            $this->authChecker,
            $this->logoutUrlGenerator,
            $this->adminUrlGenerator,
            $this->menuItemMatcher,
            $this->translationIdGenerator,
        );
    }

    public function testCreateMainMenuReturnsMainMenuDto(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createDashboardMenuItem('Home');

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $this->assertCount(1, $result->getItems());
    }

    public function testCreateMainMenuFiltersUnauthorizedItems(): void
    {
        $this->setupAdminContext();
        $this->authChecker
            ->method('isGranted')
            ->willReturnCallback(static function (string $permission, MenuItemDto $item) {
                $label = $item->getLabel();
                $labelStr = $label instanceof TranslatableInterface ? $label->getMessage() : $label;

                return 'Secret' !== $labelStr;
            });
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $publicItem = $this->createDashboardMenuItem('Public');
        $secretItem = $this->createDashboardMenuItem('Secret');

        $result = $this->menuFactory->createMainMenu([$publicItem, $secretItem]);

        $items = $result->getItems();
        $this->assertCount(1, $items);
        $label = $items[0]->getLabel();
        $labelStr = $label instanceof TranslatableInterface ? $label->getMessage() : $label;
        $this->assertStringContainsString('Public', $labelStr);
    }

    public function testCreateMainMenuFiltersUnauthorizedSubitems(): void
    {
        $this->setupAdminContext();
        $this->authChecker
            ->method('isGranted')
            ->willReturnCallback(static function (string $permission, MenuItemDto $item) {
                $label = $item->getLabel();
                $labelStr = $label instanceof TranslatableInterface ? $label->getMessage() : $label;

                return 'Secret Sub' !== $labelStr;
            });
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $parentItem = $this->createSubmenuMenuItem('Parent', [
            $this->createDashboardMenuItem('Public Sub'),
            $this->createDashboardMenuItem('Secret Sub'),
        ]);

        $result = $this->menuFactory->createMainMenu([$parentItem]);

        $items = $result->getItems();
        $this->assertCount(1, $items);
        $subItems = $items[0]->getSubItems();
        $this->assertCount(1, $subItems);
        $label = $subItems[0]->getLabel();
        $labelStr = $label instanceof TranslatableInterface ? $label->getMessage() : $label;
        $this->assertStringContainsString('Public Sub', $labelStr);
    }

    public function testCreateMainMenuGeneratesUrlsForDashboardType(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin/dashboard');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createDashboardMenuItem('Dashboard');

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('/admin/dashboard', $items[0]->getLinkUrl());
    }

    public function testCreateMainMenuGeneratesUrlsForLogoutType(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->logoutUrlGenerator->method('getLogoutPath')->willReturn('/logout');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createLogoutMenuItem('Logout');

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('/logout', $items[0]->getLinkUrl());
    }

    public function testCreateMainMenuGeneratesUrlsForUrlType(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createUrlMenuItem('External', 'https://example.com');

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('https://example.com', $items[0]->getLinkUrl());
        $this->assertSame('noopener', $items[0]->getLinkRel());
    }

    public function testCreateMainMenuSetsNoopenerForUrlTypeWithoutRel(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createUrlMenuItem('External', 'https://example.com');

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('noopener', $items[0]->getLinkRel());
    }

    public function testCreateMainMenuGeneratesUrlsForSectionType(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createSectionMenuItem('Section');

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('#', $items[0]->getLinkUrl());
    }

    public function testCreateMainMenuGeneratesUrlsForExitImpersonationType(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createExitImpersonationMenuItem('Exit');

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('?_switch_user=_exit', $items[0]->getLinkUrl());
    }

    public function testCreateMainMenuGeneratesUrlsForRouteType(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('setRoute')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin/custom-route');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createRouteMenuItem('Custom', 'custom_route', ['param' => 'value']);

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('/admin/custom-route', $items[0]->getLinkUrl());
    }

    public function testCreateUserMenuReturnsUserMenuDto(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->logoutUrlGenerator->method('getLogoutPath')->willReturn('/logout');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $logoutItem = MenuItem::linkToLogout('Logout');
        $userMenu = UserMenu::new()
            ->displayUserName()
            ->setName('John Doe')
            ->addMenuItems([$logoutItem]);

        $result = $this->menuFactory->createUserMenu($userMenu);

        $this->assertSame('John Doe', $result->getName());
        $this->assertTrue($result->isNameDisplayed());
    }

    public function testCreateMainMenuGeneratesUrlsForControllerTypeCrud(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('setAll')->willReturnSelf();
        $this->adminUrlGenerator->method('setController')->willReturnSelf();
        $this->adminUrlGenerator->method('setAction')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin/category');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createControllerMenuItem(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController',
            'Categories',
        );

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('/admin/category', $items[0]->getLinkUrl());
    }

    public function testCreateMainMenuPassesCustomQueryParametersForControllerType(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('setController')->willReturnSelf();
        $this->adminUrlGenerator->method('setAction')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin/category');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $this->adminUrlGenerator->expects($this->once())
            ->method('setAll')
            ->with($this->callback(static function (array $params) {
                return 'value' === ($params['custom'] ?? null)
                    && 'other' === ($params['foo'] ?? null);
            }))
            ->willReturnSelf();

        $menuItem = $this->createControllerMenuItem(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController',
            'Categories',
            null,
            ['custom' => 'value', 'foo' => 'other'],
        );

        $this->menuFactory->createMainMenu([$menuItem]);
    }

    public function testCreateMainMenuGeneratesUrlsForControllerTypeDashboard(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('setDashboard')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createControllerMenuItem(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\DashboardController',
            'Dashboard',
        );

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('/admin', $items[0]->getLinkUrl());
    }

    public function testCreateMainMenuGeneratesUrlsForControllerTypeWithExplicitAction(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('setController')->willReturnSelf();
        $this->adminUrlGenerator->method('setAction')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin/category/new');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createControllerMenuItem(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController',
            'New Category',
            Action::NEW,
        );

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('/admin/category/new', $items[0]->getLinkUrl());
    }

    public function testCreateMainMenuGeneratesUrlsForControllerTypeWithEntityId(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->adminUrlGenerator->method('setController')->willReturnSelf();
        $this->adminUrlGenerator->method('setAction')->willReturnSelf();
        $this->adminUrlGenerator->method('setEntityId')->willReturnSelf();
        $this->adminUrlGenerator->method('generateUrl')->willReturn('/admin/category/42/edit');
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        $menuItem = $this->createControllerMenuItemWithEntityId(
            'EasyCorp\Bundle\EasyAdminBundle\Tests\Functional\Apps\DefaultApp\Controller\CategoryCrudController',
            'Edit Category',
            Action::EDIT,
            42,
        );

        $result = $this->menuFactory->createMainMenu([$menuItem]);

        $items = $result->getItems();
        $this->assertSame('/admin/category/42/edit', $items[0]->getLinkUrl());
    }

    public function testControllerTypeThrowsForMultiActionNonInvokableController(): void
    {
        $this->setupAdminContext();
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->adminUrlGenerator->method('unsetAll')->willReturnSelf();
        $this->menuItemMatcher->method('markSelectedMenuItem')->willReturnArgument(0);

        // Use a class that is not a CRUD controller, not a Dashboard, and not invokable
        $menuItem = $this->createControllerMenuItem(
            self::class,
            'Bad Item',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must call "->setAction()"');

        $this->menuFactory->createMainMenu([$menuItem]);
    }

    private function setupAdminContext(): void
    {
        $request = new Request();
        $adminContext = AdminContext::forTesting(
            RequestContext::forTesting($request),
            null,
            null,
            I18nContext::forTesting('en', 'ltr')
        );

        $this->adminContextProvider
            ->method('getContext')
            ->willReturn($adminContext);
    }

    private function createDashboardMenuItem(string $label): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_DASHBOARD);
        $item->setLabel($label);

        return $item;
    }

    private function createLogoutMenuItem(string $label): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_LOGOUT);
        $item->setLabel($label);

        return $item;
    }

    private function createUrlMenuItem(string $label, string $url): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_URL);
        $item->setLabel($label);
        $item->setLinkUrl($url);

        return $item;
    }

    private function createSectionMenuItem(string $label): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_SECTION);
        $item->setLabel($label);

        return $item;
    }

    private function createExitImpersonationMenuItem(string $label): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_EXIT_IMPERSONATION);
        $item->setLabel($label);

        return $item;
    }

    private function createRouteMenuItem(string $label, string $routeName, array $routeParameters = []): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_ROUTE);
        $item->setLabel($label);
        $item->setRouteName($routeName);
        $item->setRouteParameters($routeParameters);

        return $item;
    }

    private function createSubmenuMenuItem(string $label, array $subItems): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_SUBMENU);
        $item->setLabel($label);
        $item->setSubItems($subItems);

        return $item;
    }

    private function createControllerMenuItem(string $controllerFqcn, string $label, ?string $action = null, array $extraQueryParameters = []): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_CONTROLLER);
        $item->setLabel($label);
        $item->setRouteParameters(array_merge([
            EA::CRUD_CONTROLLER_FQCN => $controllerFqcn,
            EA::CRUD_ACTION => $action,
            EA::ENTITY_ID => null,
        ], $extraQueryParameters));

        return $item;
    }

    private function createControllerMenuItemWithEntityId(string $controllerFqcn, string $label, string $action, int|string $entityId): MenuItemDto
    {
        $item = new MenuItemDto();
        $item->setType(MenuItemDto::TYPE_CONTROLLER);
        $item->setLabel($label);
        $item->setRouteParameters([
            EA::CRUD_CONTROLLER_FQCN => $controllerFqcn,
            EA::CRUD_ACTION => $action,
            EA::ENTITY_ID => $entityId,
        ]);

        return $item;
    }
}
