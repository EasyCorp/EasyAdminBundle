<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use Symfony\Component\ExpressionLanguage\Expression;
use function Symfony\Component\Translation\t;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class Actions
{
    private function __construct(private readonly ActionConfigDto $dto)
    {
    }

    public static function new(): self
    {
        $dto = new ActionConfigDto();

        return new self($dto);
    }

    public function add(string $pageName, Action|ActionGroup|string $actionNameOrObject): self
    {
        if ($actionNameOrObject instanceof ActionGroup) {
            $this->dto->appendAction($pageName, $actionNameOrObject->getAsDto());

            return $this;
        }

        return $this->doAddAction($pageName, $actionNameOrObject);
    }

    public function addBatchAction(Action|string $actionNameOrObject): self
    {
        return $this->doAddAction(Crud::PAGE_INDEX, $actionNameOrObject, true);
    }

    public function set(string $pageName, Action|string $actionNameOrObject): self
    {
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
        // if 'delete' is removed, 'batch delete' is removed automatically (but the
        // opposite doesn't happen). This is the most common case, but user can re-add
        // the 'batch delete' action if needed manually
        if (Action::DELETE === $actionName) {
            $this->dto->removeAction($pageName, Action::BATCH_DELETE);
        }

        return $this;
    }

    /**
     * @param array<string> $orderedActionNames
     */
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

        // disable automatic ordering when reorder is called explicitly
        $this->dto->setUseAutomaticOrdering(false);

        return $this;
    }

    public function setPermission(string $actionName, string|Expression $permission): self
    {
        $this->dto->setActionPermission($actionName, $permission);

        return $this;
    }

    /**
     * @param array<string, string|Expression> $permissions Syntax: ['actionName' => 'actionPermission', ...]
     */
    public function setPermissions(array $permissions): self
    {
        $this->dto->setActionPermissions($permissions);

        return $this;
    }

    public function disable(string ...$disabledActionNames): self
    {
        // if 'delete' is disabled, 'batch delete' is disabled automatically (but the
        // opposite doesn't happen). This is the most common case, but user can re-enable
        // the 'batch delete' action if needed manually
        if (\in_array(Action::DELETE, $disabledActionNames, true)) {
            $disabledActionNames[] = Action::BATCH_DELETE;
        }

        $this->dto->disableActions($disabledActionNames);

        return $this;
    }

    public function disableAutomaticOrdering(bool $disable = true): self
    {
        $this->dto->setUseAutomaticOrdering(!$disable);

        return $this;
    }

    public function getAsDto(?string $pageName): ActionConfigDto
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
        if (Action::BATCH_DELETE === $actionName) {
            return Action::new(Action::BATCH_DELETE, t('action.delete', domain: 'EasyAdminBundle'), null)
                ->linkToCrudAction(Action::BATCH_DELETE)
                ->asDangerAction();
        }

        if (Action::NEW === $actionName) {
            return Action::new(Action::NEW, t('action.new', domain: 'EasyAdminBundle'), null)
                ->createAsGlobalAction()
                ->linkToCrudAction(Action::NEW)
                ->renderAsLink() // for BC reasons; it's OK because this is a GET action
                ->asPrimaryAction();
        }

        if (Action::EDIT === $actionName) {
            return Action::new(Action::EDIT, t('action.edit', domain: 'EasyAdminBundle'), Crud::PAGE_INDEX === $pageName ? 'internal:edit' : null)
                ->linkToCrudAction(Action::EDIT)
                ->asPrimaryAction(Crud::PAGE_DETAIL === $pageName);
        }

        if (Action::DETAIL === $actionName) {
            return Action::new(Action::DETAIL, t('action.detail', domain: 'EasyAdminBundle'), Crud::PAGE_INDEX === $pageName ? 'internal:detail' : null)
                ->linkToCrudAction(Action::DETAIL);
        }

        if (Action::INDEX === $actionName) {
            return Action::new(Action::INDEX, t('action.index', domain: 'EasyAdminBundle'))
                ->linkToCrudAction(Action::INDEX);
        }

        if (Action::DELETE === $actionName) {
            return Action::new(Action::DELETE, t('action.delete', domain: 'EasyAdminBundle'), 'internal:delete')
                ->linkToCrudAction(Action::DELETE)
                ->asDangerAction()
                ->asTextLink()
            ;
        }

        if (Action::SAVE_AND_RETURN === $actionName) {
            return Action::new(Action::SAVE_AND_RETURN, t(Crud::PAGE_EDIT === $pageName ? 'action.save' : 'action.create', domain: 'EasyAdminBundle'))
                ->asPrimaryAction()
                ->renderAsButton()
                ->setHtmlAttributes(['name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Crud::PAGE_EDIT === $pageName ? Action::EDIT : Action::NEW);
        }

        if (Action::SAVE_AND_CONTINUE === $actionName) {
            return Action::new(Action::SAVE_AND_CONTINUE, t(Crud::PAGE_EDIT === $pageName ? 'action.save_and_continue' : 'action.create_and_continue', domain: 'EasyAdminBundle'), 'internal:edit')
                ->renderAsButton()
                ->setHtmlAttributes(['name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Crud::PAGE_EDIT === $pageName ? Action::EDIT : Action::NEW);
        }

        if (Action::SAVE_AND_ADD_ANOTHER === $actionName) {
            return Action::new(Action::SAVE_AND_ADD_ANOTHER, t('action.create_and_add_another', domain: 'EasyAdminBundle'))
                ->renderAsButton()
                ->setHtmlAttributes(['name' => 'ea[newForm][btn]', 'value' => $actionName])
                ->linkToCrudAction(Action::NEW);
        }

        throw new \InvalidArgumentException(sprintf('The "%s" action is not a built-in action, so you can\'t add or configure it via its name. Either refer to one of the built-in actions or create a custom action called "%s".', $actionName, $actionName));
    }

    private function doAddAction(string $pageName, Action|string $actionNameOrObject, bool $isBatchAction = false): self
    {
        $actionName = \is_string($actionNameOrObject) ? $actionNameOrObject : (string) $actionNameOrObject;
        $action = \is_string($actionNameOrObject) ? $this->createBuiltInAction($pageName, $actionNameOrObject) : $actionNameOrObject;

        if (null !== $this->dto->getAction($pageName, $actionName)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already exists in the "%s" page, so you can\'t add it again. Instead, you can use the "updateAction()" method to update any options of an existing action.', $actionName, $pageName));
        }

        $actionDto = $action->getAsDto();
        if ($isBatchAction) {
            $actionDto->setType(Action::TYPE_BATCH);
        }

        $this->dto->appendAction($pageName, $actionDto);

        return $this;
    }
}
