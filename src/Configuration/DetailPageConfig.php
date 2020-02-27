<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;

final class DetailPageConfig
{
    use CommonPageConfigTrait;

    private $pageName = Action::DETAIL;
    private $entityViewPermission;

    public static function new(): self
    {
        $config = new self();

        $config
            ->addAction(Action::DELETE)
            ->addAction(Action::INDEX)
            ->addAction(Action::EDIT);

        return $config;
    }

    /**
     * This grants/denies access to the 'detail' action for each entity.
     */
    public function setEntityViewPermission(string $permission): self
    {
        $this->entityViewPermission = $permission;

        return $this;
    }

    public function getAsDto(): CrudPageDto
    {
        return CrudPageDto::newFromDetailPage($this->pageName, $this->title, $this->help, $this->permission, $this->entityViewPermission, $this->actions, $this->disabledActions, $this->actionUpdateCallables);
    }
}
