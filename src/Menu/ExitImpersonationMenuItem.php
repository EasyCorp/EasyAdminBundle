<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToExitImpersonation()
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
