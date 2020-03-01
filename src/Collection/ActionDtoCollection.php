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
    {
        $collection = new self();
        $collection->actionsDto = $actionsDto;

        return $collection;
    }

    public function getGlobalActions(): self
    {
        return new self(array_filter($this->actionsDto, function (ActionDto $action) {
            return $action->isGlobalAction();
        }));
    }

    public function getEntityActions(): self
    {
        return new self(array_filter($this->actionsDto, function (ActionDto $action) {
            return $action->isEntityAction();
        }));
    }

    /**
     * @return EntityDto[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->actionsDto);
    }
}
