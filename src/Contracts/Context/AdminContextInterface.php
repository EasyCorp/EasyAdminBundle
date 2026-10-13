<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Contracts\Context;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Registry\AdminControllerRegistryInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ComponentDefaultsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ThemeDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @template TEntity of object
 *
 * @method ComponentDefaultsDto getDashboardComponentDefaults()
 */
interface AdminContextInterface
{
    public function getRequest(): Request;

    public function getI18n(): I18nDto;

    public function getAdminControllers(): AdminControllerRegistryInterface;

    /**
     * @return EntityDto<TEntity>
     */
    public function getEntity(): EntityDto;

    public function getUser(): ?UserInterface;

    public function getAssets(): AssetsDto;

    public function getAbsoluteUrls(): bool;

    public function getDashboardTitle(): string;

    public function getDashboardFaviconPath(): string;

    public function getDashboardControllerFqcn(): string;

    public function getDashboardRouteName(): string;

    public function getDashboardContentWidth(): string;

    public function getDashboardSidebarWidth(): string;

    public function getDashboardHasDarkModeEnabled(): bool;

    public function getDashboardDefaultColorScheme(): string;

    public function getDashboardTheme(): ?ThemeDto;

    /**
     * @return LocaleDto[]
     */
    public function getDashboardLocales(): array;

    public function getMainMenu(): MainMenuDto;

    public function getUserMenu(): UserMenuDto;

    public function getCrud(): ?CrudDto;

    public function getSearch(): ?SearchDto;

    public function getTemplatePath(string $templateName): string;
}
