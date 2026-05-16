<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Collection;

use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionGroupDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ActionCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @param array<string, ActionDto|ActionGroupDto> $actions
     */
    public function __construct(private array $actions = [])
    {
    }

    public function __clone()
    {
        foreach ($this->actions as $actionName => $actionDto) {
            $this->actions[$actionName] = clone $actionDto;
        }
    }

    /**
     * @return array<string, ActionDto|ActionGroupDto>
     */
    public function all(): array
    {
        return $this->actions;
    }

    public function get(string $actionName): ActionDto|ActionGroupDto|null
    {
        return $this->actions[$actionName] ?? null;
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->actions);
    }

    public function offsetGet(mixed $offset): ActionDto|ActionGroupDto
    {
        return $this->actions[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->actions[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->actions[$offset]);
    }

    public function count(): int
    {
        return \count($this->actions);
    }

    /**
     * @return \ArrayIterator<string, ActionDto|ActionGroupDto>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->actions);
    }

    public function getGlobalActions(): self
    {
        return new self(array_filter(
            $this->actions,
            static fn (ActionDto|ActionGroupDto $action): bool => $action->isGlobalAction()
        ));
    }

    public function getBatchActions(): self
    {
        return new self(array_filter(
            $this->actions,
            static fn (ActionDto|ActionGroupDto $action): bool => $action instanceof ActionDto && $action->isBatchAction()
        ));
    }
}
