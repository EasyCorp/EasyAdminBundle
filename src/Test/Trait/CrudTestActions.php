<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Test\Trait;

trait CrudTestActions
{
    protected function clickOnIndexEntityAction(string|int $entityId, string $action): void
    {
        // TODO : to implement
    }

    /**
     * @param array<array-key, string> $entityIds
     */
    protected function clickOnIndexGlobalAction(string $globalAction, array $entityIds = []): void
    {
        // TODO : to implement
    }

    protected function goToNextIndexPage(): void
    {
        // TODO : to implement
    }

    protected function goToPreviousIndexPage(): void
    {
        // TODO : to implement
    }

    protected function clickOnMenuItem(string $menuDisplayName): void
    {
        // TODO : to implement
    }
}
