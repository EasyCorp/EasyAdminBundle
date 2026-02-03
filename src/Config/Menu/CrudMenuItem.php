<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;

/**
 * @deprecated Since 4.28.1 and will be removed in 5.0.0. Use EntityMenuItem instead.
 * @see \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud()
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * @phpstan-ignore-next-line
 */
final class CrudMenuItem extends EntityMenuItem implements MenuItemInterface
{
}
