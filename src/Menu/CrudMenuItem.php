<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Menu;

use EasyCorp\Bundle\EasyAdminBundle\Dto\MenuItemDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\MenuFactory;

/**
 * @internal Instead of this, use EasyCorp\Bundle\EasyAdminBundle\Configuration\MenuItem::linkToCrud()
 */
final class CrudMenuItem
{
    use CommonMenuItemOptionsTrait;
    use LinkMenuItemOptionsTrait;
    private $entityFqcn;
    private $crudControllerFqcn;
    private $crudAction;
    private $entityId;

    public function __construct(string $label, ?string $icon, string $entityFqcn)
    {
        $this->label = $label;
        $this->icon = $icon;
        $this->entityFqcn = $entityFqcn;
        $this->crudAction = 'index';
    }

    public function setCrudController(string $controllerFqcn): self
    {
        $this->crudControllerFqcn = $controllerFqcn;

        return $this;
    }

    public function setCrudAction(string $actionName): self
    {
        $this->crudAction = $actionName;

        return $this;
    }

    public function setEntityId($entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getAsDto()
    {
        $routeParameters = [
            'crudAction' => $this->crudAction,
            'crudController' => $this->crudControllerFqcn,
            'entityFqcn' => $this->entityFqcn,
            'entityId' => $this->entityId,
        ];

        return new MenuItemDto(MenuFactory::ITEM_TYPE_CRUD, $this->label, $this->icon, $this->permission, $this->cssClass, null, $routeParameters, null, $this->linkRel, $this->linkTarget, $this->translationDomain, $this->translationParameters, null);
    }
}
