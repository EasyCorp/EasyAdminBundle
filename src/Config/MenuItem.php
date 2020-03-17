<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Menu\DashboardMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Menu\ExitImpersonationMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Menu\LogoutMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Menu\RouteMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Menu\SectionMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Menu\SubMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Menu\UrlMenuItem;

/**
 * @method MenuItemDto getAsDto()
 */
final class MenuItem
{
    /**
     * @internal Don't use this constructor; use the named constructors
     */
    private function __construct()
    {
    }

    public static function linkToCrud(string $label, ?string $icon, string $entityFqcn): CrudMenuItem
    {
        return new CrudMenuItem($label, $icon, $entityFqcn);
    }

    public static function linktoDashboard(string $label, ?string $icon = null): DashboardMenuItem
    {
        return new DashboardMenuItem($label, $icon);
    }

    public static function linkToExitImpersonation(string $label, ?string $icon = null): ExitImpersonationMenuItem
    {
        return new ExitImpersonationMenuItem($label, $icon);
    }

    public static function linkToLogout(string $label, ?string $icon = null): LogoutMenuItem
    {
        return new LogoutMenuItem($label, $icon);
    }

    public static function linktoRoute(string $label, ?string $icon = null, string $routeName, array $routeParameters = []): RouteMenuItem
    {
        return new RouteMenuItem($label, $icon, $routeName, $routeParameters);
    }

    public static function linkToUrl(string $label, ?string $icon, string $url): UrlMenuItem
    {
        return new UrlMenuItem($label, $icon, $url);
    }

    public static function section(string $label = null, ?string $icon = null): SectionMenuItem
    {
        return new SectionMenuItem($label, $icon);
    }

    public static function subMenu(string $label, ?string $icon): SubMenuItem
    {
        return new SubMenuItem($label, $icon);
    }
}
