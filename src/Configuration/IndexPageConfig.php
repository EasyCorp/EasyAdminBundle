<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudPageDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;

final class IndexPageConfig
{
    private $pageName = 'index';
    private $title;
    private $help;
    private $defaultSort = [];
    private $entityViewPermission;
    private $searchProperties = [];
    private $paginatorPageSize = 15;
    private $paginatorFetchJoinCollection = true;
    private $paginatorUseOutputWalkers;
    /** @var Action[] */
    private $actions = [];
    private $showEntityActionsAsDropdown = false;
    private $filters;

    public static function new(): self
    {
        $config = new self();

        $config
            ->addAction(Action::new('edit', 'action.edit', null)
                ->linkToCrudAction('edit')
                ->setCssClass('')
                ->setTranslationDomain('EasyAdminBundle'))

            ->addAction(Action::new('delete', 'action.delete')
                ->linkToCrudAction('delete')
                ->setCssClass('text-danger')
                ->setTranslationDomain('EasyAdminBundle'));

        return $config;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function setHelp(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    /**
     * @param array $sortAndOrder ['propertyName' => 'ASC|DESC', ...]
     */
    public function setDefaultSort(array $sortAndOrder): self
    {
        $sortAndOrder = array_map('strtoupper', $sortAndOrder);
        foreach ($sortAndOrder as $sortField => $sortOrder) {
            if (!\in_array($sortOrder, ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException(sprintf('The sort order can be only "ASC" or "DESC", "%s" given.', $sortOrder));
            }

            if (!\is_string($sortField)) {
                throw new \InvalidArgumentException(sprintf('The keys of the array that defines the default sort must be strings with the property names, but the given "%s" value is a "%s".', $sortField, \gettype($sortField)));
            }
        }

        $this->defaultSort = $sortAndOrder;

        return $this;
    }

    public function setEntityViewPermission(string $permission): self
    {
        $this->entityViewPermission = $permission;

        return $this;
    }

    public function setSearchProperties(?array $propertyNames): self
    {
        $this->searchProperties = $propertyNames;

        return $this;
    }

    public function setPaginatorPageSize(int $maxResultsPerPage): self
    {
        if ($maxResultsPerPage < 1) {
            throw new \InvalidArgumentException(sprintf('The minimum value of paginator page size is 1.'));
        }

        $this->paginatorPageSize = $maxResultsPerPage;

        return $this;
    }

    public function setPaginatorFetchJoinCollection(bool $fetchJoinCollection): self
    {
        $this->paginatorFetchJoinCollection = $fetchJoinCollection;

        return $this;
    }

    public function setPaginatorUseOutputWalkers(bool $useOutputWalkers): self
    {
        $this->paginatorUseOutputWalkers = $useOutputWalkers;

        return $this;
    }

    public function addAction(Action $actionConfig): self
    {
        $actionName = (string) $actionConfig;
        if (\array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action already exists. You can use the "updateAction()" method to update any property of an existing action.', $actionName));
        }

        $this->actions[$actionName] = $actionConfig;

        return $this;
    }

    public function updateAction(string $actionName, callable $actionConfigurator): self
    {
        if (!\array_key_exists($actionName, $this->actions)) {
            throw new \InvalidArgumentException(sprintf('The "%s" action does not exist, so you cannot update its properties. You can use the "addAction()" method to define the action first.', $actionName));
        }

        $this->actions[$actionName] = $actionConfigurator($this->actions[$actionName]);

        return $this;
    }

    public function setActionOrder(string ...$orderedActionNames): self
    {
        $orderedActions = [];
        foreach ($orderedActionNames as $actionName) {
            if (!\array_key_exists($actionName, $this->actions)) {
                throw new \InvalidArgumentException(sprintf('The "%s" action does not exist, so you cannot set its order in the list of actions.', $actionName));
            }

            $orderedActions[$actionName] = $this->actions[$actionName];
        }

        // add the remaining actions that weren't ordered explicitly. This allows
        // user to only configure the actions they want to see first and rely on the
        // existing order for the rest of actions
        foreach ($this->actions as $actionName => $actionConfig) {
            if (!array_key_exists($actionName, $orderedActions)) {
                $orderedActions[$actionName] = $actionConfig;
            }
        }

        $this->actions = $orderedActions;

        return $this;
    }

    public function removeActions(string ...$actionNames): self
    {
        foreach ($actionNames as $actionName) {
            if (!\array_key_exists($actionName, $this->actions)) {
                throw new \InvalidArgumentException(sprintf('The "%s" action cannot be removed because it does not exist.', $actionName));
            }

            unset($this->actions[$actionName]);
        }

        return $this;
    }

    public function showEntityActionsAsDropdown(bool $showAsDropdown = true): self
    {
        $this->showEntityActionsAsDropdown = $showAsDropdown;

        return $this;
    }

    public function setFilters(?array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    public function getAsDto(): CrudPageDto
    {
        return CrudPageDto::newFromIndexPage($this->pageName, $this->title, $this->help, $this->defaultSort, $this->entityViewPermission, $this->searchProperties, $this->actions, $this->showEntityActionsAsDropdown, $this->filters, new PaginatorDto($this->paginatorPageSize, $this->paginatorFetchJoinCollection, $this->paginatorUseOutputWalkers));
    }
}
