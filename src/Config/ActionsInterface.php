<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionConfigDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface ActionsInterface
{
    public function add(
        string $pageName,
        Action|string $actionNameOrObject
    ): Actions;

    public function addBatchAction(Action|string $actionNameOrObject): Actions;

    public function set(
        string $pageName,
        Action|string $actionNameOrObject
    ): Actions;

    public function update(
        string $pageName,
        string $actionName,
        callable $callable
    ): Actions;

    public function remove(string $pageName, string $actionName): Actions;

    public function reorder(
        string $pageName,
        array $orderedActionNames
    ): Actions;

    public function setPermission(
        string $actionName,
        string $permission
    ): Actions;

    /**
     * @param array $permissions Syntax: ['actionName' => 'actionPermission', ...]
     */
    public function setPermissions(array $permissions): Actions;

    public function disable(string ...$disabledActionNames): Actions;

    public function getAsDto(?string $pageName): ActionConfigDtoInterface;
}
