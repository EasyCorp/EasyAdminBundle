<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;

final class DetailPageConfig
{
    private $pageName = 'detail';
    private $title;
    private $help;
    private $entityViewPermission;
    /** @var Action[] */
    private $actions = [];

    public static function new(): self
    {
        $config = new self();

        $config
            ->addAction(Action::new('delete', 'action.delete', 'trash-o')
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

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setHelp(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function setEntityViewPermission(string $permission): self
    {
        $this->entityViewPermission = $permission;

        return $this;
    }

    public function addAction(Action $actionConfig): self
    {
        $actionName = (string) $actionConfig;
        if (\array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already exists. You can use the "updateAction()" method to update any property of an existing action.', $actionName));
        }

        $this->actions[$actionName] = $actionConfig;

        return $this;
    }

    public function updateAction(string $actionName, array $actionProperties): self
    {
        if (!\array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action does not exist, so you cannot update its properties. You can use the "addAction()" method to define the action first.', $actionName));
        }

        $this->actions[$actionName] = $this->actions[$actionName]->with($actionProperties);

        return $this;
    }

    public function getAsDto(): CrudPageDto
    {
        return CrudPageDto::newFromDetailPage($this->pageName, $this->title, $this->help, $this->entityViewPermission, $this->actions);
    }
}
