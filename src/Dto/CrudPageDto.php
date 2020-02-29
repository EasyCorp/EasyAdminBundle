<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class CrudPageDto
{
    private $actions;
    private $disabledActions;
    private $actionUpdateCallables;
    private $permission;
    private $entityPermission;

    public static function newFromIndexPage(string $name, ?string $title, ?string $help, array $defaultSort, ?string $permission, ?string $entityViewPermission, ?array $searchFields, ?array $actions, array $disabledActions, array $actionUpdateCallables, bool $showEntityActionsAsDropdown, ?array $filters, PaginatorDto $paginatorDto): self
    {
        $context = new self();

        $context->permission = $permission;
        $context->entityPermission = $entityViewPermission;
        $context->actions = $actions;
        $context->disabledActions = $disabledActions;
        $context->actionUpdateCallables = $actionUpdateCallables;

        return $context;
    }

    public static function newFromDetailPage(string $pageName, ?string $title, ?string $help, ?string $permission, ?string $entityViewPermission, array $actions, array $disabledActions, array $actionUpdateCallables): self
    {
        $context = new self();

        $context->permission = $permission;
        $context->entityPermission = $entityViewPermission;
        $context->actions = $actions;
        $context->disabledActions = $disabledActions;
        $context->actionUpdateCallables = $actionUpdateCallables;

        return $context;
    }

    public static function newFromFormPage(string $pageName, ?string $title, ?string $help, ?string $permission, array $formOptions, ?array $actions, array $disabledActions, array $actionUpdateCallables): self
    {
        $context = new self();

        $context->permission = $permission;
        $context->actions = $actions;
        $context->disabledActions = $disabledActions;
        $context->actionUpdateCallables = $actionUpdateCallables;

        return $context;
    }


    public function getActions(): ?array
    {
        return $this->actions;
    }

    public function getDisabledActions(): array
    {
        return $this->disabledActions;
    }

    public function getActionUpdateCallables(): array
    {
        return $this->actionUpdateCallables;
    }


    public function getPermission(): ?string
    {
        return $this->permission;
    }

    public function getEntityPermission(): ?string
    {
        return $this->entityPermission;
    }

}
