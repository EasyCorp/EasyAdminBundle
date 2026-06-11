<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\DashboardDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;

/**
 * Encapsulates dashboard-related data for the admin context.
 * Don't use this class directly; use @AdminContext class instead.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class DashboardContext
{
    // menus are lazy-loaded to avoid circular dependencies during AdminContext creation;
    // the menu builders are set by AdminContextFactory and executed on first access.
    private ?MainMenuDto $mainMenu = null;
    private ?UserMenuDto $userMenu = null;

    /** @var (callable(): MainMenuDto)|null */
    private $mainMenuBuilder;
    /** @var (callable(): UserMenuDto)|null */
    private $userMenuBuilder;

    public function __construct(
        private readonly DashboardDto $dashboardDto,
        private readonly string $dashboardControllerFqcn,
        private readonly AssetsDto $assets,
        private readonly bool $usePrettyUrls,
    ) {
    }

    /**
     * Sets the lazy builder for the main menu.
     * The builder is a callable that returns MainMenuDto.
     *
     * @internal Used by AdminContextFactory
     */
    public function setMainMenuBuilder(callable $builder): void
    {
        $this->mainMenuBuilder = $builder;
    }

    /**
     * Sets the lazy builder for the user menu.
     * The builder is a callable that returns UserMenuDto.
     *
     * @internal Used by AdminContextFactory
     */
    public function setUserMenuBuilder(callable $builder): void
    {
        $this->userMenuBuilder = $builder;
    }

    public function getDashboardDto(): DashboardDto
    {
        return $this->dashboardDto;
    }

    public function getDashboardControllerFqcn(): string
    {
        return $this->dashboardControllerFqcn;
    }

    public function getMainMenu(): MainMenuDto
    {
        if (null === $this->mainMenu) {
            if (null === $this->mainMenuBuilder) {
                // return empty menu (this is mostly a fallback for testing)
                $this->mainMenu = new MainMenuDto([]);
            } else {
                $this->mainMenu = ($this->mainMenuBuilder)();
            }
        }

        return $this->mainMenu;
    }

    public function getUserMenu(): UserMenuDto
    {
        if (null === $this->userMenu) {
            if (null === $this->userMenuBuilder) {
                // return empty menu (this is mostly a fallback for testing)
                $this->userMenu = UserMenu::new()->getAsDto();
            } else {
                $this->userMenu = ($this->userMenuBuilder)();
            }
        }

        return $this->userMenu;
    }

    public function getAssets(): AssetsDto
    {
        return $this->assets;
    }

    public function usePrettyUrls(): bool
    {
        return $this->usePrettyUrls;
    }

    /**
     * Creates a DashboardContext instance suitable for testing.
     *
     * When using forTesting(), menus are provided directly (not lazy-loaded).
     */
    public static function forTesting(
        ?DashboardDto $dashboardDto = null,
        string $dashboardControllerFqcn = 'App\Controller\Admin\DashboardController',
        ?MainMenuDto $mainMenu = null,
        ?UserMenuDto $userMenu = null,
        ?AssetsDto $assets = null,
        bool $usePrettyUrls = false,
    ): self {
        if (null === $dashboardDto) {
            // create a new DashboardDto with required defaults for tests
            $dto = Dashboard::new()->getAsDto();
            $dto->setRouteName('admin');
        } else {
            $dto = $dashboardDto;
        }

        $context = new self(
            $dto,
            $dashboardControllerFqcn,
            $assets ?? new AssetsDto(),
            $usePrettyUrls
        );

        // set menus directly for testing (no lazy loading needed)
        $context->mainMenu = $mainMenu ?? new MainMenuDto([]);
        $context->userMenu = $userMenu ?? UserMenu::new()->getAsDto();

        return $context;
    }
}
