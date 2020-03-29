<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

final class CrudDto
{
    private $pageName;
    private $actionName;
    /** @var $actions ActionConfigDto */
    private $actionConfigDto;
    private $filters;
    private $entityFqcn;
    private $entityLabelInSingular;
    private $entityLabelInPlural;
    private $defaultPageTitles = [
        Crud::PAGE_DETAIL => 'page_title.detail',
        Crud::PAGE_EDIT => 'page_title.edit',
        Crud::PAGE_INDEX => 'page_title.index',
        Crud::PAGE_NEW => 'page_title.new',
    ];
    private $customPageTitles;
    private $helpMessages;
    private $dateFormat;
    private $timeFormat;
    private $dateTimePattern;
    private $dateIntervalFormat;
    private $timezone;
    private $numberFormat;
    private $defaultSort;
    private $searchFields;
    private $showEntityActionsAsDropdown;
    /** @var PaginatorDto */
    private $paginatorDto;
    private $overriddenTemplates;
    private $formThemes;
    private $formOptions;
    private $entityPermission;

    public function __construct()
    {
        $this->customPageTitles = [Crud::PAGE_DETAIL => null, Crud::PAGE_EDIT => null, Crud::PAGE_INDEX => null, Crud::PAGE_NEW => null];
        $this->helpMessages = [Crud::PAGE_DETAIL => null, Crud::PAGE_EDIT => null, Crud::PAGE_INDEX => null, Crud::PAGE_NEW => null];
        $this->dateFormat = 'medium';
        $this->timeFormat = 'medium';
        $this->dateTimePattern = '';
        $this->dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
        $this->defaultSort = [];
        $this->searchFields = [];
        $this->showEntityActionsAsDropdown = false;
        $this->formThemes = ['@EasyAdmin/crud/form_theme.html.twig'];
        $this->formOptions = [];
        $this->overriddenTemplates = [];
    }

    public function getCurrentPage(): ?string
    {
        return $this->pageName;
    }

    public function setPageName(string $pageName): void
    {
        $this->pageName = $pageName;
    }

    public function getEntityFqcn(): string
    {
        return $this->entityFqcn;
    }

    public function setEntityFqcn(string $entityFqcn): void
    {
        $this->entityFqcn = $entityFqcn;
    }

    public function getEntityLabelInSingular(): ?string
    {
        return $this->entityLabelInSingular;
    }

    public function setEntityLabelInSingular(string $label): void
    {
        $this->entityLabelInSingular = $label;
    }

    public function getEntityLabelInPlural(): ?string
    {
        return $this->entityLabelInPlural;
    }

    public function setEntityLabelInPlural(string $label): void
    {
        $this->entityLabelInPlural = $label;
    }

    public function getCustomPageTitle(string $pageName = null): ?string
    {
        return $this->customPageTitles[$pageName ?? $this->pageName] ?? null;
    }

    public function setCustomPageTitle(string $pageName, string $pageTitle): void
    {
        $this->customPageTitles[$pageName] = $pageTitle;
    }

    public function getDefaultPageTitle(string $pageName = null): ?string
    {
        return $this->defaultPageTitles[$pageName ?? $this->pageName] ?? null;
    }

    public function getHelpMessage(string $pageName = null): string
    {
        return $this->helpMessages[$pageName ?? $this->pageName] ?? '';
    }

    public function getHelpMessages(): array
    {
        return $this->helpMessages;
    }

    public function setHelpMessage(string $pageName, string $helpMessage): void
    {
        $this->helpMessages[$pageName] = $helpMessage;
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(?string $format): void
    {
        $this->dateFormat = $format;
    }

    public function getTimeFormat(): ?string
    {
        return $this->timeFormat;
    }

    public function setTimeFormat(?string $format): void
    {
        $this->timeFormat = $format;
    }

    public function getDateTimePattern(): string
    {
        return $this->dateTimePattern;
    }

    public function setDateTimePattern(string $pattern): void
    {
        $this->dateTimePattern = $pattern;
    }

    public function getDateIntervalFormat(): string
    {
        return $this->dateIntervalFormat;
    }

    public function setDateIntervalFormat(string $format): void
    {
        $this->dateIntervalFormat = $format;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezoneId): void
    {
        $this->timezone = $timezoneId;
    }

    public function getNumberFormat(): ?string
    {
        return $this->numberFormat;
    }

    public function setNumberFormat(string $numberFormat): void
    {
        $this->numberFormat = $numberFormat;
    }

    public function getDefaultSort(): array
    {
        return $this->defaultSort;
    }

    public function setDefaultSort(array $defaultSort): void
    {
        $this->defaultSort = $defaultSort;
    }

    public function getSearchFields(): ?array
    {
        return $this->searchFields;
    }

    public function setSearchFields(?array $searchFields): void
    {
        $this->searchFields = $searchFields;
    }

    public function isSearchEnabled(): bool
    {
        return null !== $this->searchFields;
    }

    public function showEntityActionsAsDropdown(): bool
    {
        return $this->showEntityActionsAsDropdown;
    }

    public function setShowEntityActionsAsDropdown(bool $showAsDropdown): void
    {
        $this->showEntityActionsAsDropdown = $showAsDropdown;
    }

    public function getPaginator(): PaginatorDto
    {
        return $this->paginatorDto;
    }

    public function setPaginator(PaginatorDto $paginatorDto): void
    {
        $this->paginatorDto = $paginatorDto;
    }

    public function getOverriddenTemplates(): array
    {
        return $this->overriddenTemplates;
    }

    public function overrideTemplate(string $templateName, string $templatePath): void
    {
        $this->overriddenTemplates[$templateName] = $templatePath;
    }

    public function getFormThemes(): array
    {
        return $this->formThemes;
    }

    public function setFormThemes(array $formThemes): void
    {
        $this->formThemes = $formThemes;
    }

    public function getFormOptions(): ?array
    {
        return $this->formOptions;
    }

    public function setFormOptions(array $formOptions): void
    {
        $this->formOptions = $formOptions;
    }

    public function getEntityPermission(): ?string
    {
        return $this->entityPermission;
    }

    public function setEntityPermission(string $entityPermission): void
    {
        $this->entityPermission = $entityPermission;
    }

    public function getCurrentAction(): string
    {
        return $this->actionName;
    }

    public function setCurrentAction(string $actionName): void
    {
        $this->actionName = $actionName;
    }

    public function getActionConfig(): ActionConfigDto
    {
        return $this->actionConfigDto;
    }

    public function setActionConfig(ActionConfigDto $actionConfig): void
    {
        $this->actionConfigDto = $actionConfig;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }
}
