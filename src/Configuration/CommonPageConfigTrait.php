<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

trait CommonPageConfigTrait
{
    private $title;
    private $help;
    private $permission;
    /** @var Action[] */
    private $actions = [];
    /** @var string[] */
    private $disabledActions = [];

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

    /**
     * This grants/denies access to the entire action
     */
    public function setPermission(string $permission): self
    {
        $this->permission = $permission;

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

    public function updateAction(string $actionName, callable $actionConfigurator): self
    {
        if (!\array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action does not exist, so you cannot update its properties. You can use the "addAction()" method to define the action first.', $actionName));
        }

        $this->actions[$actionName] = $actionConfigurator($this->actions[$actionName]);

        return $this;
    }

    public function setActionOrder(string ...$orderedActionNames): self
    {
        $orderedActions = [];
        foreach ($orderedActionNames as $actionName) {
            if (!\array_key_exists($actionName, $this->actions)) {
                throw new \InvalidArgumentException(sprintf('The "%s" action does not exist, so you cannot set its order in the list of actions.', $actionName));
            }

            $orderedActions[$actionName] = $this->actions[$actionName];
        }

        // add the remaining actions that weren't ordered explicitly. This allows
        // user to only configure the actions they want to see first and rely on the
        // existing order for the rest of actions
        foreach ($this->actions as $actionName => $actionConfig) {
            if (!array_key_exists($actionName, $orderedActions)) {
                $orderedActions[$actionName] = $actionConfig;
            }
        }

        $this->actions = $orderedActions;

        return $this;
    }

    public function disableActions(string ...$actionNames): self
    {
        foreach ($actionNames as $actionName) {
            $this->disabledActions[] = $actionName;
        }

        return $this;
    }
}
