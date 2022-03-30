<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * @see EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToModal()
 *
 * @author Florent Diedler <fdiedler2000@gmail.com>
 */
final class ModalMenuItem implements MenuItemInterface
{
    use MenuItemTrait;

    public function __construct(string $label, ?string $icon, string $tag, string $templatePath)
    {
        $this->dto = new MenuItemDto();

        $this->dto->setType(MenuItemDto::TYPE_MODAL);
        $this->dto->setLabel($label);
        $this->dto->setIcon($icon);
        $this->dto->setLinkModal($tag);
        $this->dto->setTemplatePathModal($templatePath);
    }
}
