<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Context\CrudPageContext;

final class DetailPageConfig
{
    private $title;
    private $help;
    /** @var Action[] */
    private $actions = [];

    public static function new(): self
    {
        return new self();
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

    public function addAction(Action $actionConfig): self
    {
        $actionName = (string) $actionConfig;
        if (array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already exists. You can use the "updateAction()" method to update any property of an existing action.', $actionName));
        }

        $this->actions[$actionName] = $actionConfig;

        return $this;
    }

    public function updateAction(string $actionName, array $actionProperties): self
    {
        if (!array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already does not exist, so you cannot update its properties. You can use the "addAction()" method to define the action first.', $actionName));
        }

        $this->actions[$actionName] = $this->actions[$actionName]->withProperties($actionProperties);

        return $this;
    }

    public function getAsValueObject(): CrudPageContext
    {
        return CrudPageContext::newFromDetailPage($this->title, $this->help, $this->actions);
    }
}
