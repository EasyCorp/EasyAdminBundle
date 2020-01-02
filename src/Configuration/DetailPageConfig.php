<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;

final class DetailPageConfig
{
    use CommonPageConfigTrait;

    private $pageName = 'detail';
    private $entityViewPermission;

    public static function new(): self
    {
        $config = new self();

        $config
            ->addAction(Action::new('delete', 'action.delete', 'fa fa-fw fa-trash-o')
                ->linkToCrudAction('delete')
                ->setCssClass('btn btn-link pr-0 text-danger')
                ->setTranslationDomain('EasyAdminBundle'))

            ->addAction(Action::new('index', 'action.list', null)
                ->linkToCrudAction('index')
                ->setCssClass('btn')
                ->setTranslationDomain('EasyAdminBundle'))

            ->addAction(Action::new('edit', 'action.edit', null)
                ->linkToCrudAction('edit')
                ->setCssClass('btn btn-primary')
                ->setTranslationDomain('EasyAdminBundle'));

        return $config;
    }

    /**
     * This grants/denies access to the 'detail' action for each entity
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
