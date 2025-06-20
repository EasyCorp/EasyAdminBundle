<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionConfigDto
{
    private ?string $pageName = null;
    /**
     * @var array<string,array<string,ActionDto>>
     */
    private array $actions = [
        Crud::PAGE_DETAIL => [],
        Crud::PAGE_EDIT => [],
        Crud::PAGE_INDEX => [],
        Crud::PAGE_NEW => [],
    ];
    /** @var string[] */
    private array $disabledActions = [];
    /** @var array<string, string|Expression> */
    private array $actionPermissions = [];

    public function __construct()
    {
    }

    public function __clone()
    {
        foreach ($this->actions as $pageName => $actions) {
            foreach ($actions as $actionName => $actionDto) {
                $this->actions[$pageName][$actionName] = clone $actionDto;
            }
        }
    }

    /**
     * @deprecated since 4.25.0 and it will be removed in EasyAdmin 5.0.0.
     */
    public function setPageName(?string $pageName): void
    {
        $this->pageName = $pageName;
    }

    public function setActionPermission(string $actionName, string|Expression $permission): void
    {
        $this->actionPermissions[$actionName] = $permission;
    }

    /**
     * @param array<string, string|Expression> $permissions
     */
    public function setActionPermissions(array $permissions): void
    {
        $this->actionPermissions = $permissions;
    }

    public function prependAction(string $pageName, ActionDto $actionDto): void
    {
        $this->actions[$pageName][$actionDto->getName()] = $actionDto;
    }

    public function appendAction(string $pageName, ActionDto $actionDto): void
    {
        $this->actions[$pageName] = array_merge([$actionDto->getName() => $actionDto], $this->actions[$pageName]);
    }

    public function setAction(string $pageName, ActionDto $actionDto): void
    {
        $this->actions[$pageName][$actionDto->getName()] = $actionDto;
    }

    public function getAction(string $pageName, string $actionName): ?ActionDto
    {
        return $this->actions[$pageName][$actionName] ?? null;
    }

    public function removeAction(string $pageName, string $actionName): void
    {
        unset($this->actions[$pageName][$actionName]);
    }

    /**
     * @param array<string> $orderedActionNames
     */
    public function reorderActions(string $pageName, array $orderedActionNames): void
    {
        $orderedActions = [];
        foreach ($orderedActionNames as $actionName) {
            $orderedActions[$actionName] = $this->actions[$pageName][$actionName];
        }

        $this->actions[$pageName] = $orderedActions;
    }

    /**
     * @param array<string> $actionNames
     */
    public function disableActions(array $actionNames): void
    {
        foreach ($actionNames as $actionName) {
            if (!\in_array($actionName, $this->disabledActions, true)) {
                $this->disabledActions[] = $actionName;
            }
        }
    }

    /**
     * @return ActionCollection|array<string,array<string,ActionDto>>
     *
     * @deprecated since 4.25.0 and it will be removed in EasyAdmin 5.0.0. Use `getPageActions` or `getActionList` instead.
     */
    public function getActions(): ActionCollection|array
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'Calling "%s" is deprecated and will be removed in 5.0.0. Use `getPageActions` or `getActionList` instead.',
            __METHOD__,
        );

        return null === $this->pageName ? $this->actions : ActionCollection::new($this->actions[$this->pageName]);
    }

    public function getPageActions(string $pageName): ActionCollection
    {
        return ActionCollection::new($this->actions[$pageName]);
    }

    /**
     * @return array<string,array<string,ActionDto>>
     */
    public function getActionList(): array
    {
        return $this->actions;
    }

    /**
     * @param array<string, ActionDto> $newActions
     *
     * @deprecated since 4.25.0 and it will be removed in EasyAdmin 5.0.0. Use `setPageActions` instead.
     */
    public function setActions(string $pageName, array $newActions): void
    {
        trigger_deprecation(
            'easycorp/easyadmin-bundle',
            '4.25.0',
            'Calling "%s" is deprecated and will be removed in 5.0.0. Use `setPageActions` instead.',
            __METHOD__,
        );

        $this->actions[$pageName] = $newActions;
    }

    /**
     * @param array<string, ActionDto> $newActions
     */
    public function setPageActions(string $pageName, array $newActions): void
    {
        $this->actions[$pageName] = $newActions;
    }

    /**
     * @return array<string>
     */
    public function getDisabledActions(): array
    {
        return $this->disabledActions;
    }

    /**
     * @return array<string|Expression>
     */
    public function getActionPermissions(): array
    {
        return $this->actionPermissions;
    }
}
