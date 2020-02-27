<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

final class ActionCollection
{
    private const BATCH_ACTION = 'batch_action';
    private const ACTION = 'action';
    private const ROW_ACTION = 'row_action';

    // $actions = ['item' => ['detail' => $action1, 'index' => $action2], 'global' => ..., 'batch' => ...]
    private $actions = [];

    public static function new(): self
    {
        return new self();
    }

    public function addBatchAction(Action $actionConfig): self
    {
        $this->actions[self::BATCH_ACTION][(string) $actionConfig] = $actionConfig;

        return $this;
    }

    public function addRowAction(Action $actionConfig): self
    {
        $this->actions[self::ROW_ACTION][(string) $actionConfig] = $actionConfig;

        return $this;
    }

    public function addAction(string $page, Action $actionConfig): self
    {
        $validPageNames = [Action::INDEX, Action::DETAIL, 'form'];
        if (!\in_array($page, $validPageNames)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action is added to the "%s" page, but it must be added to one of the following valid pages: %s.', (string) $actionConfig, $page, implode(', ', $validPageNames)));
        }

        $this->actions[self::ACTION][(string) $actionConfig] = $actionConfig;

        return $this;
    }

    public function removeBatchAction(string $actionName): self
    {
        if (!\array_key_exists($actionName, $this->actions[self::BATCH_ACTION])) {
            throw new \InvalidArgumentException(sprintf('The given "%s" action cannot be removed from the list of batch actions because it\'s not an action of type "batch". Maybe it\'s a "row action" or a normal action?', $actionName));
        }

        unset($this->actions[self::BATCH_ACTION][$actionName]);
    }

    public function removeRowAction(string $actionName): self
    {
        if (!\array_key_exists($actionName, $this->actions[self::ROW_ACTION])) {
            throw new \InvalidArgumentException(sprintf('The given "%s" action cannot be removed from the list of row actions because it\'s not an action of type "row". Maybe it\'s a "batch action" or a normal action?', $actionName));
        }

        unset($this->actions[self::ROW_ACTION][$actionName]);
    }

    public function removeAction(string $pageName, string $actionName): self
    {
        if (!\array_key_exists($actionName, $this->actions[self::ACTION][$pageName])) {
            throw new \InvalidArgumentException(sprintf('The given "%s" action cannot be removed from the list of "%s" actions because it\'s not defined for that page. Maybe it\'s associated to a different page or it\'s another type of action ("batch action" or "row action")?', $actionName, $pageName));
        }

        unset($this->actions[self::ACTION][$actionName][$pageName]);
    }

    public function getBatchActions(): array
    {
        return $this->actions[self::BATCH_ACTION];
    }

    public function getRowActions(): array
    {
        return $this->actions[self::ROW_ACTION];
    }

    public function getActions(): array
    {
        return $this->actions[self::ACTION];
    }
}
