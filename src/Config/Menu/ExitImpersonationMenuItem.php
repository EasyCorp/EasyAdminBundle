<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * @see EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToExitImpersonation()
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ExitImpersonationMenuItem implements MenuItemInterface
{
    use MenuItemTrait;

    public function __construct(string $label, ?string $icon)
    {
        $this->dto = new MenuItemDto();

        $this->dto->setType(MenuItemDto::TYPE_EXIT_IMPERSONATION);
        $this->dto->setLabel($label);
        $this->dto->setIcon($icon);
    }
}
