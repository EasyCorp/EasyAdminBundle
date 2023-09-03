<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use EasyCorp\Bundle\EasyAdminBundle\Collection\ActionCollection;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ActionConfigDtoInterface
{
    public function setPageName(?string $pageName): void;

    public function setActionPermission(string $actionName, string $permission): void;

    public function setActionPermissions(array $permissions): void;

    public function prependAction(string $pageName, ActionDtoInterface $actionDto): void;

    public function appendAction(string $pageName, ActionDtoInterface $actionDto): void;

    public function setAction(string $pageName, ActionDtoInterface $actionDto): void;

    public function getAction(string $pageName, string $actionName): ?ActionDtoInterface;

    public function removeAction(string $pageName, string $actionName): void;

    public function reorderActions(string $pageName, array $orderedActionNames): void;

    public function disableActions(array $actionNames): void;

    public function getActions(): ActionCollection|array;

    /**
     * @param ActionDtoInterface[] $newActions
     */
    public function setActions(string $pageName, array $newActions): void;

    public function getDisabledActions(): array;

    public function getActionPermissions(): array;
}
