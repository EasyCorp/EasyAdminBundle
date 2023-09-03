<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Context;


use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\AssetsDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\I18nDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\LocaleDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MainMenuDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\UserMenuDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Registry\CrudControllerRegistryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A context object that stores all the state and config of the current admin request.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface AdminContextInterface
{
    public function getRequest(): Request;

    public function getReferrer(): ?string;

    public function getI18n(): I18nDtoInterface;

    public function getCrudControllers(): CrudControllerRegistryInterface;

    public function getEntity(): EntityDtoInterface;

    public function getUser(): ?UserInterface;

    public function getAssets(): AssetsDtoInterface;

    public function getSignedUrls(): bool;

    public function getAbsoluteUrls(): bool;

    public function getDashboardTitle(): string;

    public function getDashboardFaviconPath(): string;

    public function getDashboardControllerFqcn(): string;

    public function getDashboardRouteName(): string;

    public function getDashboardContentWidth(): string;

    public function getDashboardSidebarWidth(): string;

    public function getDashboardHasDarkModeEnabled(): bool;

    /**
     * @return LocaleDtoInterface[]
     */
    public function getDashboardLocales(): array;

    public function getMainMenu(): MainMenuDtoInterface;

    public function getUserMenu(): UserMenuDtoInterface;

    public function getCrud(): ?CrudDtoInterface;

    public function getSearch(): ?SearchDtoInterface;

    public function getTemplatePath(string $templateName): string;
}
