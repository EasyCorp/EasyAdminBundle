<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Context;

final class CrudPageContext
{
    private $title;
    private $help;
    private $actions;
    private $maxResults;
    private $searchFields;
    private $filters;
    private $formThemes;
    private $formOptions;

    public static function newFromIndexPage(?string $title, ?string $help, int $maxResults, ?array $searchFields, ?array $filters): self
    {
        $context = new self();

        $context->title = $title;
        $context->help = $help;
        $context->maxResults = $maxResults;
        $context->searchFields = $searchFields;
        $context->filters = $filters;

        return $context;
    }

    public static function newFromDetailPage(?string $title, ?string $help, array $actions): self
    {
        $context = new self();

        $context->title = $title;
        $context->help = $help;
        $context->actions = $actions;

        return $context;
    }

    public static function newFromFormPage(?string $title, ?string $help, array $formThemes, array $formOptions): self
    {
        $context = new self();

        $context->title = $title;
        $context->help = $help;
        $context->formThemes = $formThemes;
        $context->formOptions = $formOptions;

        return $context;
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

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    public function getSearchFields(): ?array
    {
        return $this->searchFields;
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
