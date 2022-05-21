<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @see EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::section()
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class SectionMenuItem implements MenuItemInterface
{
    use MenuItemTrait {
        setLinkRel as private;
        setLinkTarget as private;
    }

    public function __construct(TranslatableInterface|string|null $label, ?string $icon)
    {
        $this->dto = new MenuItemDto();

        $this->dto->setType(MenuItemDto::TYPE_SECTION);
        $this->dto->setLabel($label ?? '');
        $this->dto->setIcon($icon);
    }
}
