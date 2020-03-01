<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;

final class ActionDtoCollection implements \IteratorAggregate
{
    /** @var ActionDto[] */
    private $actionsDto;

    private function __construct()
    {
    }

    public static function new(array $actionsDto = null): self
    {dump($actionsDto);
        $collection = new self();
        $collection->actionsDto = $actionsDto;

        return $collection;
    }

    /**
     * @return ActionDto[]
     */
    public function getDisabledActions(): array
    {
        return array_filter($this->actionsDto, static function (ActionDto $action) {
            return $action->isDisabledAction();
        });
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

    /**
     * @return ActionDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->actionsDto);
    }
}
