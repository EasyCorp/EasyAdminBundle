<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToCrud()
 */
final class CrudMenuItem implements MenuItemInterface
{
    use MenuItemTrait;

    public function __construct(string $label, ?string $icon, string $entityFqcn)
    {
        $this->dto = new MenuItemDto();

        $this->dto->setType(MenuItemDto::TYPE_CRUD);
        $this->dto->setLabel($label);
        $this->dto->setIcon($icon);
        $this->dto->setRouteParameters([
            'crudAction' => 'index',
            'crudController' => null,
            'entityFqcn' => $entityFqcn,
            'entityId' => null,
        ]);
    }

    public function setCrudController(string $controllerFqcn): self
    {
        $this->dto->setRouteParameters(array_merge(
            $this->dto->getRouteParameters(),
            ['crudController' => $controllerFqcn]
        ));

        return $this;
    }

    public function setCrudAction(string $actionName): self
    {
        $this->dto->setRouteParameters(array_merge(
            $this->dto->getRouteParameters(),
            ['crudAction' => $actionName]
        ));

        return $this;
    }

    public function setEntityId($entityId): self
    {
        $this->dto->setRouteParameters(array_merge(
            $this->dto->getRouteParameters(),
            ['entityId' => $entityId]
        ));

        return $this;
    }
}
