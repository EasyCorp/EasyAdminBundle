<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class ActionConfigDto
{
    /** @var ActionDto[] */
    private $batchActionsDto;
    /** @var ActionDto[] */
    private $actionsDto;
    /** @var string[] */
    private $disabledActions;
    /** @var string[] */
    private $actionPermissions;

    private function __construct()
    {
    }

    public static function new(array $actionsDto = [], array $disabledActions = [], array $actionPermissions = []): self
    {
        $collection = new self();
        $collection->actionsDto = $actionsDto;
        $collection->disabledActions = $disabledActions;
        $collection->actionPermissions = $actionPermissions;

        return $collection;
    }

    /**
     * @return ActionDto[]
     */
    public function getActions(): array
    {
        return $this->actionsDto;
    }

    /**
     * @param ActionDto[] $newActions
     */
    public function updateBatchActions(array $newBatchActionsDto): self
    {
        $this->batchActionsDto = $newBatchActionsDto;

        return $this;
    }

    /**
     * @param ActionDto[] $newActions
     */
    public function updateActions(array $newActionsDto): self
    {
        $this->actionsDto = $newActionsDto;

        return $this;
    }

    public function getDisabledActions(): array
    {
        return $this->disabledActions;
    }

    public function getActionPermissions(): array
    {
        return $this->actionPermissions;
    }

    /**
     * @return ActionDto[]
     */
    public function getGlobalActions(): array
    {
        return array_filter($this->actionsDto, static function (ActionDto $action) {
            return $action->isGlobalAction();
        });
    }

    /**
     * @return ActionDto[]
     */
    public function getBatchActions(): array
    {
        return array_filter($this->actionsDto, static function (ActionDto $action) {
            return $action->isBatchAction();
        });
    }

    /**
     * @return ActionDto[]
     */
    public function getEntityActions(): array
    {
        return array_filter($this->actionsDto, static function (ActionDto $action) {
            return $action->isEntityAction();
        });
    }
}
