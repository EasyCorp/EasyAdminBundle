<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\Action;

final class CrudDto
{
    use PropertyAccessorTrait;
    use PropertyModifierTrait;

    private $pageName;
    private $actionName;
    private $entityFqcn;
    private $labelInSingular;
    private $labelInPlural;
    private $defaultPageTitles = [
        Action::DETAIL => 'page.detail.title',
        Action::EDIT => 'page.edit.title',
        Action::INDEX => 'page.index.title',
        Action::NEW => 'page.new.title',
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
    private $pagePermission;
    private $entityPermission;
    /** @var CrudPageDto */
    private $crudPageDto;
    private $disabledActions;

    public function __construct(?string $entityFqcn, string $labelInSingular, string $labelInPlural, array $pageTitles, array $helpMessages, ?string $dateFormat, ?string $timeFormat, string $dateTimePattern, string $dateIntervalFormat, ?string $timezone, ?string $numberFormat, array $defaultSort, ?array $searchFields, bool $showEntityActionsAsDropdown, ?array $filters, PaginatorDto $paginatorDto, TemplateDtoCollection $overriddenTemplates, $formThemes, array $formOptions, ?string $pagePermission, ?string $entityPermission, array $disabledActions)
    {
        $this->entityFqcn = $entityFqcn;
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
        $this->pagePermission = $pagePermission;
        $this->entityPermission = $entityPermission;
        $this->disabledActions = $disabledActions;
    }

    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    public function getLabelInSingular(): string
    {
        return $this->labelInSingular;
    }

    public function getLabelInPlural(): string
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

    public function getPagePermission(): ?string
    {
        return $this->pagePermission;
    }

    public function getEntityPermission(): ?string
    {
        return $this->entityPermission;
    }

    public function getPage(): ?CrudPageDto
    {
        return $this->crudPageDto;
    }

    public function getAction(): string
    {
        return $this->actionName;
    }

    public function getDisabledActions(): array
    {
        return $this->disabledActions;
    }
}
