<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\CrudConfig;

final class CrudDto
{
    use PropertyAccessorTrait;
    use PropertyModifierTrait;

    private $pageName;
    private $actionName;
    /** @var ActionConfigDto */
    private $actions;
    private $entityFqcn;
    private $labelInSingular;
    private $labelInPlural;
    private $defaultPageTitles = [
        CrudConfig::PAGE_DETAIL => 'page_title.detail',
        CrudConfig::PAGE_EDIT => 'page_title.edit',
        CrudConfig::PAGE_INDEX => 'page_title.index',
        CrudConfig::PAGE_NEW => 'page_title.new',
    ];
    private $customPageTitles;
    private $helpMessages;
    private $dateFormat;
    private $timeFormat;
    private $dateTimePattern;
    private $dateIntervalFormat;
    private $timezone;
    private $numberFormat;
    private $defaultSort = [];
    private $searchFields;
    private $showEntityActionsAsDropdown;
    private $filters;
    /** @var PaginatorDto */
    private $paginatorDto;
    private $overriddenTemplates;
    private $formThemes;
    private $formOptions;
    private $entityPermission;

    public function __construct(?string $labelInSingular, ?string $labelInPlural, array $pageTitles, array $helpMessages, ?string $dateFormat, ?string $timeFormat, string $dateTimePattern, string $dateIntervalFormat, ?string $timezone, ?string $numberFormat, array $defaultSort, ?array $searchFields, bool $showEntityActionsAsDropdown, ?array $filters, PaginatorDto $paginatorDto, TemplateDtoCollection $overriddenTemplates, $formThemes, array $formOptions, ?string $entityPermission)
    {
        $this->labelInSingular = $labelInSingular;
        $this->labelInPlural = $labelInPlural;
        $this->customPageTitles = $pageTitles;
        $this->helpMessages = $helpMessages;
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->dateTimePattern = $dateTimePattern;
        $this->dateIntervalFormat = $dateIntervalFormat;
        $this->timezone = $timezone;
        $this->numberFormat = $numberFormat;
        $this->defaultSort = $defaultSort;
        $this->searchFields = $searchFields;
        $this->showEntityActionsAsDropdown = $showEntityActionsAsDropdown;
        $this->filters = $filters;
        $this->paginatorDto = $paginatorDto;
        $this->overriddenTemplates = $overriddenTemplates;
        $this->formThemes = $formThemes;
        $this->formOptions = $formOptions;
        $this->entityPermission = $entityPermission;
    }

    public function getCurrentPage(): ?string
    {
        return $this->pageName;
    }

    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    public function getLabelInSingular(): ?string
    {
        return $this->labelInSingular;
    }

    public function getLabelInPlural(): ?string
    {
        return $this->labelInPlural;
    }

    public function getCustomPageTitle(string $pageName = null): ?string
    {
        return $this->customPageTitles[$pageName ?? $this->pageName] ?? null;
    }

    public function getDefaultPageTitle(string $pageName = null): ?string
    {
        return $this->defaultPageTitles[$pageName ?? $this->pageName] ?? null;
    }

    public function getHelpMessage(string $pageName = null): string
    {
        return $this->helpMessages[$pageName ?? $this->pageName] ?? '';
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function getTimeFormat(): ?string
    {
        return $this->timeFormat;
    }

    public function getDateTimePattern(): string
    {
        return $this->dateTimePattern;
    }

    public function getDateIntervalFormat(): string
    {
        return $this->dateIntervalFormat;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function getNumberFormat(): ?string
    {
        return $this->numberFormat;
    }

    public function getDefaultSort(): array
    {
        return $this->defaultSort;
    }

    public function getSearchFields(): ?array
    {
        return $this->searchFields;
    }

    public function isSearchEnabled(): bool
    {
        return null !== $this->searchFields;
    }

    public function showEntityActionsAsDropdown(): bool
    {
        return $this->showEntityActionsAsDropdown;
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

    public function getFormThemes(): array
    {
        return $this->formThemes;
    }

    public function getFormOptions(): ?array
    {
        return $this->formOptions;
    }

    public function getEntityPermission(): ?string
    {
        return $this->entityPermission;
    }

    public function getCurrentAction(): string
    {
        return $this->actionName;
    }

    public function getActions(): ActionConfigDto
    {
        return $this->actions;
    }
}
