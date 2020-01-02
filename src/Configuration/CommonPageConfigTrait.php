<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

trait CommonPageConfigTrait
{
    private $title;
    private $help;
    private $permission;
    /** @var Action[] */
    private $actions = [];
    /** @var callable[] */
    private $actionUpdateCallables = [];
    /** @var string[] */
    private $disabledActions = [];
    /** @internal */
    private $builtInActions;

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

    /**
     * @param Action|string $actionNameOrConfig
     */
    public function addAction($actionNameOrConfig): self
    {
        $actionName = (string) $actionNameOrConfig;

        if (\array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already exists. You can use the "updateAction()" method to update any property of an existing action.', $actionName));
        }

        $this->actions[$actionName] = $actionNameOrConfig;

        return $this;
    }

    public function updateAction(string $actionName, callable $updateCallable): self
    {
        $this->actionUpdateCallables[$actionName] = $updateCallable;

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

    private function getBuiltInActions(): array
    {
        if (null !== $this->builtInActions) {
            return $this->builtInActions;
        }

        return $this->builtInActions = [
            'index' => Action::new('index', 'action.index')
                ->linkToCrudAction('index')
                ->setTranslationDomain('EasyAdminBundle'),

            'detail' => Action::new('detail', 'action.detail')
                ->linkToCrudAction('detail')
                ->setTranslationDomain('EasyAdminBundle'),

            'edit' => Action::new('edit', 'action.edit', null)
                ->linkToCrudAction('edit')
                ->setCssClass('')
                ->setTranslationDomain('EasyAdminBundle'),

            'delete' => Action::new('delete', 'action.delete')
                ->linkToCrudAction('delete')
                ->setCssClass('text-danger')
                ->setTranslationDomain('EasyAdminBundle'),

            'save-and-return' => Action::new('save-and-return', 'action.save')
                ->linkToCrudAction('edit')
                ->setTranslationDomain('EasyAdminBundle'),

            'save-and-continue' => Action::new('save-and-continue', 'action.save_and_continue')
                ->linkToCrudAction('edit')
                ->setTranslationDomain('EasyAdminBundle'),
        ];
    }
}
