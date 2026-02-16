<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\CrudControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Factory\MenuFactoryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemMatcherInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Translation\EntityTranslationIdGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Contracts\Translation\TranslatableInterface;
use function Symfony\Component\Translation\t;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class MenuFactory implements MenuFactoryInterface
{
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly AuthorizationCheckerInterface $authChecker,
        private readonly LogoutUrlGenerator $logoutUrlGenerator,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
        private readonly MenuItemMatcherInterface $menuItemMatcher,
        private readonly ?EntityTranslationIdGeneratorInterface $entityTranslationIdGenerator = null,
    ) {
        if (null === $this->entityTranslationIdGenerator) {
            trigger_deprecation(
                'easycorp/easyadmin-bundle',
                '4.28',
                'Not passing argument "$entityTranslationIdGenerator" will cause an error in 5.0.0.',
                '$entityTranslationIdGenerator',
            );
        }
    }

    /**
     * @param array<MenuItemDto|MenuItemInterface> $menuItems
     */
    public function createMainMenu(array $menuItems): MainMenuDto
    {
        return new MainMenuDto($this->buildMenuItems($menuItems));
    }

    public function createUserMenu(UserMenu $userMenu): UserMenuDto
    {
        $userMenuDto = $userMenu->getAsDto();
        $builtUserMenuItems = $this->buildMenuItems($userMenuDto->getItems());
        $userMenuDto->setItems($builtUserMenuItems);

        return $userMenuDto;
    }

    /**
     * @param array<MenuItemDto|MenuItemInterface> $menuItems
     *
     * @return MenuItemDto[]
     */
    private function buildMenuItems(array $menuItems): array
    {
        /** @var AdminContext $adminContext */
        $adminContext = $this->adminContextProvider->getContext();
        $translationDomain = $adminContext->getI18n()->getTranslationDomain() ?? '';

        $builtItems = [];
        foreach ($menuItems as $menuItem) {
            if ($menuItem instanceof MenuItemDto) {
                $menuItemDto = $menuItem;
            } else {
                $menuItemDto = $menuItem->getAsDto();
            }

            if (false === $this->authChecker->isGranted(Permission::EA_VIEW_MENU_ITEM, $menuItemDto)) {
                continue;
            }

            $subItems = [];
            foreach ($menuItemDto->getSubItems() as $menuSubItemDto) {
                if (false === $this->authChecker->isGranted(Permission::EA_VIEW_MENU_ITEM, $menuSubItemDto)) {
                    continue;
                }

                $subItems[] = $this->buildMenuItem($menuSubItemDto, [], $translationDomain, $adminContext->isUseEntityTranslations());
            }

            $builtItems[] = $this->buildMenuItem($menuItemDto, $subItems, $translationDomain, $adminContext->isUseEntityTranslations());
        }

        $builtItems = $this->menuItemMatcher->markSelectedMenuItem($builtItems, $adminContext->getRequest());

        return $builtItems;
    }

    /**
     * @param MenuItemDto[] $subItems
     */
    private function buildMenuItem(MenuItemDto $menuItemDto, array $subItems, string $translationDomain, bool $isUseEntityTranslations): MenuItemDto
    {
        if (!$menuItemDto->getLabel() instanceof TranslatableInterface) {
            $label = $menuItemDto->getLabel();
            if (null === $label && MenuItemDto::TYPE_CRUD === $menuItemDto->getType()) {
                if ($isUseEntityTranslations) {
                    $label = Action::INDEX === $menuItemDto->getRouteParameters()[EA::CRUD_ACTION]
                        ? $this->entityTranslationIdGenerator->generateForEntity($menuItemDto->getRouteParameters()[EA::ENTITY_FQCN], false)
                        : $this->entityTranslationIdGenerator->generateForEntity($menuItemDto->getRouteParameters()[EA::ENTITY_FQCN], true);
                } else {
                    $label = basename(str_replace('\\', '/', $menuItemDto->getRouteParameters()[EA::ENTITY_FQCN]));
                }
            } elseif (null === $label && MenuItemDto::TYPE_CONTROLLER === $menuItemDto->getType()) {
                $controllerFqcn = $menuItemDto->getRouteParameters()[EA::CRUD_CONTROLLER_FQCN];
                if ($isUseEntityTranslations && is_a($controllerFqcn, CrudControllerInterface::class, true)) {
                    $entityFqcn = $controllerFqcn::getEntityFqcn();
                    $action = $menuItemDto->getRouteParameters()[EA::CRUD_ACTION] ?? Action::INDEX;
                    $label = Action::INDEX === $action
                        ? $this->entityTranslationIdGenerator->generateForEntity($entityFqcn, false)
                        : $this->entityTranslationIdGenerator->generateForEntity($entityFqcn, true);
                } else {
                    $label = basename(str_replace('\\', '/', $controllerFqcn));
                    $label = preg_replace('/(Crud)?Controller$/', '', $label);
                }
            } else {
                $label = '' === $label ? $label : t($label, $menuItemDto->getTranslationParameters(), $translationDomain);
            }
            $menuItemDto->setLabel($label);
        }

        $url = $this->generateMenuItemUrl($menuItemDto);
        $menuItemDto->setLinkUrl($url);

        $menuItemDto->setSubItems($subItems);

        // if menu item points to an absolute URL and no 'rel' attribute is defined,
        // assign the 'rel="noopener"' attribute for performance and security reasons.
        // see https://web.dev/external-anchors-use-rel-noopener/
        if ('' === $menuItemDto->getLinkRel() && MenuItemDto::TYPE_URL === $menuItemDto->getType()) {
            $menuItemDto->setLinkRel('noopener');
        }

        return $menuItemDto;
    }

    private function generateMenuItemUrl(MenuItemDto $menuItemDto): string
    {
        $menuItemType = $menuItemDto->getType();

        if (MenuItemDto::TYPE_CRUD === $menuItemType) {
            $routeParameters = $menuItemDto->getRouteParameters();

            $this->adminUrlGenerator
                // remove all existing query params to avoid keeping search queries, filters and pagination
                ->unsetAll()
                // set any other parameters defined by the menu item
                ->setAll($routeParameters);

            $entityFqcn = $routeParameters[EA::ENTITY_FQCN] ?? null;
            $crudControllerFqcn = $routeParameters[EA::CRUD_CONTROLLER_FQCN] ?? null;
            if (null === $entityFqcn && null === $crudControllerFqcn) {
                throw new \RuntimeException(sprintf('The CRUD menu item with label "%s" must define either the entity FQCN (using the third constructor argument) or the CRUD Controller FQCN (using the "setController()" method).', $menuItemDto->getLabel()));
            }

            // 1. if CRUD controller is defined, use it...
            if (null !== $crudControllerFqcn) {
                $this->adminUrlGenerator->setController($crudControllerFqcn);
            // 2. ...otherwise, find the CRUD controller from the entityFqcn
            } else {
                $adminControllers = $this->adminContextProvider->getContext()?->getAdminControllers(); // @phpstan-ignore method.notFound (method will be added to the interface in 5.0)
                if (null === $controllerFqcn = $adminControllers->findCrudControllerByEntity($entityFqcn)) {
                    throw new \RuntimeException(sprintf('Unable to find the controller related to the "%s" Entity; did you forget to extend "%s"?', $entityFqcn, AbstractCrudController::class));
                }

                $this->adminUrlGenerator->setController($controllerFqcn);
                $this->adminUrlGenerator->unset(EA::ENTITY_FQCN);
            }

            return $this->adminUrlGenerator->generateUrl();
        }

        if (MenuItemDto::TYPE_CONTROLLER === $menuItemType) {
            $routeParameters = $menuItemDto->getRouteParameters();
            $controllerFqcn = $routeParameters[EA::CRUD_CONTROLLER_FQCN];

            $this->adminUrlGenerator->unsetAll();

            if (is_a($controllerFqcn, DashboardControllerInterface::class, true)) {
                return $this->adminUrlGenerator
                    ->setDashboard($controllerFqcn)
                    ->generateUrl();
            }

            $action = $routeParameters[EA::CRUD_ACTION] ?? null;
            if (null === $action) {
                if (is_a($controllerFqcn, CrudControllerInterface::class, true)) {
                    $action = Action::INDEX;
                } elseif (method_exists($controllerFqcn, '__invoke')) {
                    $action = '__invoke';
                } else {
                    throw new \RuntimeException(sprintf('The menu item that links to the "%s" controller must call "->setAction()" to specify which action to link to, because the controller defines multiple actions and is not invokable.', $controllerFqcn));
                }
            }

            $this->adminUrlGenerator->setController($controllerFqcn);
            $this->adminUrlGenerator->setAction($action);

            if (null !== ($routeParameters[EA::ENTITY_ID] ?? null)) {
                $this->adminUrlGenerator->setEntityId($routeParameters[EA::ENTITY_ID]);
            }
            if (isset($routeParameters[EA::SORT])) {
                $this->adminUrlGenerator->set(EA::SORT, $routeParameters[EA::SORT]);
            }

            return $this->adminUrlGenerator->generateUrl();
        }

        if (MenuItemDto::TYPE_DASHBOARD === $menuItemType) {
            return $this->adminUrlGenerator->unsetAll()->generateUrl();
        }

        if (MenuItemDto::TYPE_ROUTE === $menuItemType) {
            return $this->adminUrlGenerator
                ->unsetAll()
                ->setRoute($menuItemDto->getRouteName(), $menuItemDto->getRouteParameters())
                ->generateUrl();
        }

        if (MenuItemDto::TYPE_SECTION === $menuItemType) {
            return '#';
        }

        if (MenuItemDto::TYPE_URL === $menuItemType) {
            return $menuItemDto->getLinkUrl();
        }

        if (MenuItemDto::TYPE_LOGOUT === $menuItemType) {
            return $this->logoutUrlGenerator->getLogoutPath();
        }

        if (MenuItemDto::TYPE_EXIT_IMPERSONATION === $menuItemType) {
            // the switch parameter name can be changed, but this code assumes it's always
            // the default one because Symfony doesn't provide a generic exitImpersonationUrlGenerator
            return '?_switch_user=_exit';
        }

        return '';
    }
}
