<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class CrudPageDto
{
    private $name;
    private $title;
    private $help;
    private $actions;
    private $defaultSort;
    private $maxResults;
    private $itemPermission;
    private $searchFields;
    private $paginatorFetchJoinCollection;
    private $paginatorUseOutputWalkers;
    private $filters;
    private $formThemes;
    private $formOptions;

    public static function newFromIndexPage(string $name, ?string $title, ?string $help, array $defaultSort, int $maxResults, ?string $itemPermission, ?array $searchFields, bool $paginatorFetchJoinCollection, ?bool $paginatorUseOutputWalkers, ?array $filters): self
    {
        $context = new self();

        $context->name = $name;
        $context->title = $title;
        $context->help = $help;
        $context->defaultSort = $defaultSort;
        $context->maxResults = $maxResults;
        $context->itemPermission = $itemPermission;
        $context->searchFields = $searchFields;
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

    public static function newFromFormPage(string $pageName, ?string $title, ?string $help, array $formThemes, array $formOptions): self
    {
        $context = new self();

        $context->name = $pageName;
        $context->title = $title;
        $context->help = $help;
        $context->formThemes = $formThemes;
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

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
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

    public function getPaginatorFetchJoinCollection(): bool
    {
        return $this->paginatorFetchJoinCollection;
    }

    public function getPaginatorUseOutputWalkers(): ?bool
    {
        return $this->paginatorUseOutputWalkers;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function getFormThemes(): ?array
    {
        return $this->formThemes;
    }

    public function getFormOptions(): ?array
    {
        return $this->formOptions;
    }
}
