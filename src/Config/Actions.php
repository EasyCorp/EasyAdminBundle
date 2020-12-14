<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Actions
{
    /** @var ActionConfigDto */
    private $dto;

    private function __construct(ActionConfigDto $actionConfigDto)
    {
        $this->dto = $actionConfigDto;
    }

    public static function new(): self
    {
        $dto = new ActionConfigDto();

        return new self($dto);
    }

    /**
     * @param string|Action $actionNameOrObject
     */
    public function add(string $pageName, $actionNameOrObject): self
    {
        if (!\is_string($actionNameOrObject) && !$actionNameOrObject instanceof Action) {
            throw new \InvalidArgumentException(sprintf('The argument of "%s" can only be either a string with the action name or a "%s" object with the action config.', __METHOD__, Action::class));
        }

        $actionName = \is_string($actionNameOrObject) ? $actionNameOrObject : (string) $actionNameOrObject;
        $action = \is_string($actionNameOrObject) ? $this->createBuiltInAction($pageName, $actionNameOrObject) : $actionNameOrObject;

        if (null !== $this->dto->getAction($pageName, $actionName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already exists in the "%s" page, so you can\'t add it again. Instead, you can use the "updateAction()" method to update any options of an existing action.', $actionName, $pageName));
        }

        if (Crud::PAGE_INDEX === $pageName && Action::DELETE === $actionName) {
            $this->dto->prependAction($pageName, $action->getAsDto());
        } else {
            $this->dto->appendAction($pageName, $action->getAsDto());
        }

        return $this;
    }

    /**
     * @param string|Action $actionNameOrObject
     */
    public function set(string $pageName, $actionNameOrObject): self
    {
        if (!\is_string($actionNameOrObject) && !$actionNameOrObject instanceof Action) {
            throw new \InvalidArgumentException(sprintf('The argument of "%s" can only be either a string with the action name or a "%s" object with the action config.', __METHOD__, Action::class));
        }

        $action = \is_string($actionNameOrObject) ? $this->createBuiltInAction($pageName, $actionNameOrObject) : $actionNameOrObject;

        $this->dto->appendAction($pageName, $action->getAsDto());

        return $this;
    }

    public function update(string $pageName, string $actionName, callable $callable): self
    {
        if (null === $actionDto = $this->dto->getAction($pageName, $actionName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action does not exist in the "%s" page, so you cannot update it. Instead, add the action with the "add()" method.', $actionName, $pageName));
        }

        $action = $actionDto->getAsConfigObject();
        /** @var Action $action */
        $action = $callable($action);
        $this->dto->setAction($pageName, $action->getAsDto());

        return $this;
    }

    public function remove(string $pageName, string $actionName): self
    {
        if (null === $this->dto->getAction($pageName, $actionName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action does not exist in the "%s" page, so you cannot remove it.', $actionName, $pageName));
        }

        $this->dto->removeAction($pageName, $actionName);

        return $this;
    }

    public function reorder(string $pageName, array $orderedActionNames): self
    {
        $newActionOrder = [];
        $currentActions = $this->dto->getActions();
        foreach ($orderedActionNames as $actionName) {
            if (!\array_key_exists($actionName, $currentActions[$pageName])) {
                throw new \InvalidArgumentException(sprintf('The "%s" action does not exist in the "%s" page, so you cannot set its order.', $actionName, $pageName));
            }

            $newActionOrder[] = $actionName;
        }

        // add the remaining actions that weren't ordered explicitly. This allows
        // user to only configure the actions they want to see first and rely on the
        // existing order for the rest of actions
        foreach ($currentActions[$pageName] as $actionName => $action) {
            if (!\in_array($actionName, $newActionOrder, true)) {
                $newActionOrder[] = $actionName;
            }
        }

        $this->dto->reorderActions($pageName, $newActionOrder);

        return $this;
    }

    public function setPermission(string $actionName, string $permission): self
    {
        $this->dto->setActionPermission($actionName, $permission);

        return $this;
    }

    /**
     * @param array $permissions Syntax: ['actionName' => 'actionPermission', ...]
     */
    public function setPermissions(array $permissions): self
    {
        $this->dto->setActionPermissions($permissions);

        return $this;
    }

    public function disable(string ...$disabledActionNames): self
    {
        $this->dto->disableActions($disabledActionNames);

        return $this;
    }

    public function getAsDto(string $pageName): ActionConfigDto
    {
        $this->dto->setPageName($pageName);

        return $this->dto;
    }

    /**
     * The $pageName is needed because sometimes the same action has different config
     * depending on where it's displayed (to display an icon in 'detail' but not in 'index', etc.).
     */
    private function createBuiltInAction(string $pageName, string $actionName): Action
    {
        if (Action::NEW === $actionName) {
            return Action::new(Action::NEW, '__ea__action.new', null)
                ->createAsGlobalAction()
                ->linkToCrudAction(Action::NEW)
                ->addCssClass('btn btn-primary');
        }

        if (Action::EDIT === $actionName) {
            return Action::new(Action::EDIT, '__ea__action.edit', null)
                ->linkToCrudAction(Action::EDIT)
                ->addCssClass(Crud::PAGE_DETAIL === $pageName ? 'btn btn-primary' : '');
        }

        if (Action::DETAIL === $actionName) {
            return Action::new(Action::DETAIL, '__ea__action.detail')
                ->linkToCrudAction(Action::DETAIL)
                ->addCssClass(Crud::PAGE_EDIT === $pageName ? 'btn btn-secondary' : '');
        }

        if (Action::INDEX === $actionName) {
            return Action::new(Action::INDEX, '__ea__action.index')
                ->linkToCrudAction(Action::INDEX)
                ->addCssClass(\in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT, Crud::PAGE_NEW], true) ? 'btn btn-secondary' : '');
        }

        if (Action::DELETE === $actionName) {
            $cssClass = \in_array($pageName, [Crud::PAGE_DETAIL, Crud::PAGE_EDIT], true) ? 'btn btn-link pr-0 text-danger' : 'text-danger';

            return Action::new(Action::DELETE, '__ea__action.delete', Crud::PAGE_INDEX === $pageName ? null : 'fa fa-fw fa-trash-o')
                ->linkToCrudAction(Action::DELETE)
                ->addCssClass($cssClass);
        }

        if (Action::SAVE_AND_RETURN === $actionName) {
            return Action::new(Action::SAVE_AND_RETURN, Crud::PAGE_EDIT === $pageName ? '__ea__action.save' : '__ea__action.create')
                ->addCssClass('btn btn-primary action-save')
                ->displayAsButton()
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Crud::PAGE_EDIT === $pageName ? Action::EDIT : Action::NEW);
        }

        if (Action::SAVE_AND_CONTINUE === $actionName) {
            return Action::new(Action::SAVE_AND_CONTINUE, Crud::PAGE_EDIT === $pageName ? '__ea__action.save_and_continue' : '__ea__action.create_and_continue', 'far fa-edit')
                ->addCssClass('btn btn-secondary action-save')
                ->displayAsButton()
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Crud::PAGE_EDIT === $pageName ? Action::EDIT : Action::NEW);
        }

        if (Action::SAVE_AND_ADD_ANOTHER === $actionName) {
            return Action::new(Action::SAVE_AND_ADD_ANOTHER, '__ea__action.create_and_add_another')
                ->addCssClass('btn btn-secondary action-save')
                ->displayAsButton()
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Action::NEW);
        }

        throw new \InvalidArgumentException(sprintf('The "%s" action is not a built-in action, so you can\'t add or configure it via its name. Either refer to one of the built-in actions or create a custom action called "%s".', $actionName, $actionName));
    }
}
