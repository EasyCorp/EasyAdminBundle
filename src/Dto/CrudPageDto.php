<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class CrudPageDto
{
    private $name;
    private $title;
    private $help;
    private $actions;
    private $defaultSort = [];
    private $entityPermission;
    private $searchFields;
    private $filters;
    private $paginatorDto;
    private $formOptions = [];
    private $showSaveAndExitButton;
    private $showSaveAndContinueButton;
    private $showSaveAndAddAnotherButton;

    public static function newFromIndexPage(string $name, ?string $title, ?string $help, array $defaultSort, ?string $entityViewPermission, ?array $searchFields, ?array $filters, PaginatorDto $paginatorDto): self
    {
        $context = new self();

        $context->name = $name;
        $context->title = $title;
        $context->help = $help;
        $context->defaultSort = $defaultSort;
        $context->entityPermission = $entityViewPermission;
        $context->searchFields = $searchFields;
        $context->filters = $filters;
        $context->paginatorDto = $paginatorDto;

        return $context;
    }

    public static function newFromDetailPage(string $pageName, ?string $title, ?string $help, ?string $entityViewPermission, array $actions): self
    {
        $context = new self();

        $context->name = $pageName;
        $context->title = $title;
        $context->help = $help;
        $context->entityPermission = $entityViewPermission;
        $context->actions = $actions;

        return $context;
    }

    public static function newFromFormPage(string $pageName, ?string $title, ?string $help, array $formOptions, bool $showSaveAndExitButton, bool $showSaveAndContinueButton, bool $showSaveAndAddAnotherButton): self
    {
        $context = new self();

        $context->name = $pageName;
        $context->title = $title;
        $context->help = $help;
        $context->formOptions = $formOptions;
        $context->showSaveAndExitButton = $showSaveAndExitButton;
        $context->showSaveAndContinueButton = $showSaveAndContinueButton;
        $context->showSaveAndAddAnotherButton = $showSaveAndAddAnotherButton;

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

    public function getEntityPermission(): ?string
    {
        return $this->entityPermission;
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

    public function getPaginator(): PaginatorDto
    {
        return $this->paginatorDto;
    }

    public function getFormOptions(): ?array
    {
        return $this->formOptions;
    }

    public function showSaveAndExitButton(): bool
    {
        return $this->showSaveAndExitButton;
    }

    public function showSaveAndContinueButton(): bool
    {
        return $this->showSaveAndContinueButton;
    }

    public function showSaveAndAddAnotherButton(): bool
    {
        return $this->showSaveAndAddAnotherButton;
    }
}
