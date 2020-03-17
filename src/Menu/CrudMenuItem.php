<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToCrud()
 */
final class CrudMenuItem
{
    use MenuItemTrait;

    public function __construct(string $label, ?string $icon, string $entityFqcn)
    {
        $this->type = MenuFactory::ITEM_TYPE_CRUD;
        $this->label = $label;
        $this->icon = $icon;
        $this->routeParameters = [
            'crudAction' => 'index',
            'crudController' => null,
            'entityFqcn' => $entityFqcn,
            'entityId' => null,
        ];
    }

    public function setCrudController(string $controllerFqcn): self
    {
        $this->routeParameters['crudController'] = $controllerFqcn;

        return $this;
    }

    public function setCrudAction(string $actionName): self
    {
        $this->routeParameters['crudAction'] = $actionName;

        return $this;
    }

    public function setEntityId($entityId): self
    {
        $this->routeParameters['entityId'] = $entityId;

        return $this;
    }
}
