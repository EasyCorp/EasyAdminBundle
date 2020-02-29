<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

final class ActionConfig
{
    /** @var Action[] */
    private $actions = [];
    /** @var callable[] */
    private $actionUpdateCallables = [];
    /** @var string[] */
    private $disabledActions = [];

    public static function new(): self
    {
        return new self();
    }

    /**
     * @param string|Action $actionNameOrObject
     */
    public function addAction(string $pageName, $actionNameOrObject): self
    {
        if (!\is_string($actionNameOrObject) && !$actionNameOrObject instanceof Action) {
            throw new \InvalidArgumentException(sprintf('The argument of "%s" can only be either a string with the action name or a "%s" object with the action config.', __METHOD__, Action::class));
        }

        $actionName = is_string($actionNameOrObject) ? $actionNameOrObject : (string) $actionNameOrObject;
        $action = is_string($actionNameOrObject) ? $this->createBuiltInAction($actionNameOrObject) : $actionNameOrObject;

        if (\array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already exists, so you can\'t add it again. Instead, you can use the "updateAction()" method to update any property of an existing action.', $actionName));
        }

        $this->actions[$pageName][$actionName] = $action;
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
            if (!\array_key_exists($actionName, $orderedActions)) {
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

    /**
     * The $pageName is needed because sometimes the same action has different config
     * depending on where it's displayed (to display an icon in 'detail' but not in 'index', etc.).
     */
    private function createBuiltInAction(string $pageName, string $actionName): Action
    {
        if (Action::EDIT === $actionName) {
            return Action::new(Action::EDIT, 'action.edit', null)
                ->linkToCrudAction(Action::EDIT)
                ->setCssClass(CrudConfig::PAGE_DETAIL === $pageName ? 'btn btn-primary' : '')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::DETAIL === $actionName) {
            return Action::new(Action::DETAIL, 'action.detail')
                ->linkToCrudAction(Action::DETAIL)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::INDEX === $actionName) {
            return Action::new(Action::INDEX, 'action.index')
                ->linkToCrudAction(Action::INDEX)
                ->setCssClass(CrudConfig::PAGE_DETAIL === $pageName ? 'btn' : '')
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::DELETE === $actionName) {
            $cssClass = CrudConfig::PAGE_DETAIL === $pageName ? 'btn btn-link pr-0 text-danger' : 'text-danger';

            return Action::new(Action::DELETE, 'action.delete', CrudConfig::PAGE_INDEX === $pageName ? null : 'fa fa-fw fa-trash-o')
                ->linkToCrudAction(Action::DELETE)
                ->setCssClass($cssClass)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::SAVE_AND_RETURN === $actionName) {
            return Action::new(Action::SAVE_AND_RETURN, CrudConfig::PAGE_EDIT === $pageName ? 'action.save' : 'action.create')
                ->setCssClass('btn btn-primary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(CrudConfig::PAGE_EDIT === $pageName ? Action::EDIT : Action::NEW)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::SAVE_AND_CONTINUE === $actionName) {
            return Action::new(Action::SAVE_AND_CONTINUE, CrudConfig::PAGE_EDIT === $pageName ? 'action.save_and_continue' : 'action.create_and_continue', 'far fa-edit')
                ->setCssClass('btn btn-secondary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(CrudConfig::PAGE_EDIT === $pageName ? Action::EDIT : Action::NEW)
                ->setTranslationDomain('EasyAdminBundle');
        }

        if (Action::SAVE_AND_ADD_ANOTHER === $actionName) {
            return Action::new(Action::SAVE_AND_ADD_ANOTHER, 'action.create_and_add_another')
                ->setCssClass('btn btn-secondary action-save')
                ->setHtmlElement('button')
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Action::NEW)
                ->setTranslationDomain('EasyAdminBundle');
        }

        throw new \InvalidArgumentException(sprintf('The "%s" action is not a built-in action, so you can\'t add or configure it via its name. Either refer to one of the built-in actions or create a custom action called "%s".', $actionName, $actionName));
    }
}
