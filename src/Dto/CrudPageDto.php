<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class CrudPageDto
{
    private $name;
    private $title;
    private $help;
    private $actions;
    private $defaultSort;
    private $itemPermission;
    private $searchFields;
    private $paginatorPageSize;
    private $paginatorFetchJoinCollection;
    private $paginatorUseOutputWalkers;
    private $filters;
    private $formOptions;

    public static function newFromIndexPage(string $name, ?string $title, ?string $help, array $defaultSort, ?string $itemPermission, ?array $searchFields, int $paginatorPageSize, bool $paginatorFetchJoinCollection, ?bool $paginatorUseOutputWalkers, ?array $filters): self
    {
        $context = new self();

        $context->name = $name;
        $context->title = $title;
        $context->help = $help;
        $context->defaultSort = $defaultSort;
        $context->itemPermission = $itemPermission;
        $context->searchFields = $searchFields;
        $context->paginatorPageSize = $paginatorPageSize;
        $context->paginatorFetchJoinCollection = $paginatorFetchJoinCollection;
        $context->paginatorUseOutputWalkers = $paginatorUseOutputWalkers;
        $context->filters = $filters;

        return $context;
    }

    public static function newFromDetailPage(string $pageName, ?string $title, ?string $help, array $actions): self
    {
        $context = new self();

        $context->name = $pageName;
        $context->title = $title;
        $context->help = $help;
        $context->actions = $actions;

        return $context;
    }

    public static function newFromFormPage(string $pageName, ?string $title, ?string $help, array $formOptions): self
    {
        $context = new self();

        $context->name = $pageName;
        $context->title = $title;
        $context->help = $help;
        $context->formOptions = $formOptions;

        return $context;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getDefaultSort(): array
    {
        return $this->defaultSort;
    }

    public function getItemPermission(): ?string
    {
        return $this->itemPermission;
    }

    public function getSearchFields(): ?array
    {
        return $this->searchFields;
    }

    public function isSearchEnabled(): bool
    {
        return null !== $this->searchFields;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function isFilterEnabled(): bool
    {
        return null !== $this->filters;
    }

    public function getPaginatorPageSize(): ?int
    {
        return $this->paginatorPageSize;
    }

    public function getPaginatorFetchJoinCollection(): bool
    {
        return $this->paginatorFetchJoinCollection;
    }

    public function getPaginatorUseOutputWalkers(): ?bool
    {
        return $this->paginatorUseOutputWalkers;
    }

    public function getFormOptions(): ?array
    {
        return $this->formOptions;
    }
}
