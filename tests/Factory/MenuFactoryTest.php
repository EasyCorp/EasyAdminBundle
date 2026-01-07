<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemMatcherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
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

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->logoutUrlGenerator = $this->createMock(LogoutUrlGenerator::class);
        $this->adminUrlGenerator = $this->createMock(AdminUrlGeneratorInterface::class);
        $this->menuItemMatcher = $this->createMock(MenuItemMatcherInterface::class);

        $this->menuFactory = new MenuFactory(
            $this->adminContextProvider,
            $this->authChecker,
            $this->logoutUrlGenerator,
            $this->adminUrlGenerator,
            $this->menuItemMatcher
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
            ->willReturnCallback(function (string $permission, MenuItemDto $item) {
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
            ->willReturnCallback(function (string $permission, MenuItemDto $item) {
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
}
