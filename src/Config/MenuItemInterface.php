<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\DashboardMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\ExitImpersonationMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\LogoutMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\RouteMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SectionMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SubMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\UrlMenuItem;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface MenuItemInterface
{
    /**
     * @param string|null $icon The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function linkToCrud(
        TranslatableInterface|string $label,
        ?string $icon,
        string $entityFqcn
    ): CrudMenuItem;

    /**
     * @param string|null $icon The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function linkToDashboard(
        TranslatableInterface|string $label,
        ?string $icon = null
    ): DashboardMenuItem;

    /**
     * @param string|null $icon The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function linkToExitImpersonation(
        TranslatableInterface|string $label,
        ?string $icon = null
    ): ExitImpersonationMenuItem;

    /**
     * @param string|null $icon The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function linkToLogout(TranslatableInterface|string $label, ?string $icon = null): LogoutMenuItem;

    /**
     * @param string|null $icon The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function linkToRoute(
        TranslatableInterface|string $label,
        ?string $icon,
        string $routeName,
        array $routeParameters = []
    ): RouteMenuItem;

    /**
     * @param string|null $icon The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function linkToUrl(TranslatableInterface|string $label, ?string $icon, string $url): UrlMenuItem;

    /**
     * @param string|null $icon The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function section(
        TranslatableInterface|string|null $label = null,
        ?string $icon = null
    ): SectionMenuItem;

    /**
     * @param string|null $icon The full CSS classes of the FontAwesome icon to render (see https://fontawesome.com/v6/search?m=free)
     */
    public static function subMenu(TranslatableInterface|string $label, ?string $icon = null): SubMenuItem;
}
