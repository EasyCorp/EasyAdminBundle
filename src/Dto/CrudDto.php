<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CrudDto
{
    private $controllerFqcn;
    private $pageName;
    private $actionName;
    /** @var ActionConfigDto */
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
    private $datePattern;
    private $timePattern;
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
    private $newFormOptions;
    private $editFormOptions;
    private $entityPermission;
    private $contentWidth;
    private $sidebarWidth;

    public function __construct()
    {
        $this->customPageTitles = [Crud::PAGE_DETAIL => null, Crud::PAGE_EDIT => null, Crud::PAGE_INDEX => null, Crud::PAGE_NEW => null];
        $this->helpMessages = [Crud::PAGE_DETAIL => null, Crud::PAGE_EDIT => null, Crud::PAGE_INDEX => null, Crud::PAGE_NEW => null];
        $this->datePattern = 'medium';
        $this->timePattern = 'medium';
        $this->dateTimePattern = ['medium', 'medium'];
        $this->dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
        $this->defaultSort = [];
        $this->searchFields = [];
        $this->showEntityActionsAsDropdown = false;
        $this->formThemes = ['@EasyAdmin/crud/form_theme.html.twig'];
        $this->newFormOptions = KeyValueStore::new();
        $this->editFormOptions = KeyValueStore::new();
        $this->overriddenTemplates = [];
    }

    public function getControllerFqcn(): ?string
    {
        return $this->controllerFqcn;
    }

    public function setControllerFqcn(string $fqcn): void
    {
        $this->controllerFqcn = $fqcn;
    }

    public function getCurrentPage(): ?string
    {
        return $this->pageName;
    }

    public function setPageName(?string $pageName): void
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

    public function getEntityLabelInSingular($entityInstance = null): ?string
    {
        if (\is_callable($this->entityLabelInSingular)) {
            return ($this->entityLabelInSingular)($entityInstance);
        }

        return $this->entityLabelInSingular;
    }

    /**
     * @param string|callable $label
     */
    public function setEntityLabelInSingular($label): void
    {
        $this->entityLabelInSingular = $label;
    }

    public function getEntityLabelInPlural($entityInstance = null): ?string
    {
        if (\is_callable($this->entityLabelInPlural)) {
            return ($this->entityLabelInPlural)($entityInstance);
        }

        return $this->entityLabelInPlural;
    }

    /**
     * @param string|callable $label
     */
    public function setEntityLabelInPlural($label): void
    {
        $this->entityLabelInPlural = $label;
    }

    public function getCustomPageTitle(string $pageName = null, $entityInstance = null): ?string
    {
        $title = $this->customPageTitles[$pageName ?? $this->pageName];
        if (\is_callable($title)) {
            return null !== $entityInstance ? $title($entityInstance) : $title();
        }

        return $title;
    }

    /**
     * @param string|callable $pageTitle
     */
    public function setCustomPageTitle(string $pageName, $pageTitle): void
    {
        $this->customPageTitles[$pageName] = $pageTitle;
    }

    public function getDefaultPageTitle(string $pageName = null, $entityInstance = null): ?string
    {
        if (null !== $entityInstance) {
            if (method_exists($entityInstance, '__toString')) {
                $entityAsString = (string) $entityInstance;

                if (!empty($entityAsString)) {
                    return $entityAsString;
                }
            }
        }

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

    public function getDatePattern(): ?string
    {
        return $this->datePattern;
    }

    public function setDatePattern(?string $format): void
    {
        $this->datePattern = $format;
    }

    public function getTimePattern(): ?string
    {
        return $this->timePattern;
    }

    public function setTimePattern(?string $format): void
    {
        $this->timePattern = $format;
    }

    public function getDateTimePattern(): array
    {
        return $this->dateTimePattern;
    }

    public function setDateTimePattern(string $dateFormatOrPattern, string $timeFormat = DateTimeField::FORMAT_NONE): void
    {
        $this->dateTimePattern = [$dateFormatOrPattern, $timeFormat];
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

    public function getNewFormOptions(): ?KeyValueStore
    {
        return $this->newFormOptions;
    }

    public function getEditFormOptions(): ?KeyValueStore
    {
        return $this->editFormOptions;
    }

    public function setNewFormOptions(KeyValueStore $formOptions): void
    {
        $this->newFormOptions = $formOptions;
    }

    public function setEditFormOptions(KeyValueStore $formOptions): void
    {
        $this->editFormOptions = $formOptions;
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

    public function getActionsConfig(): ActionConfigDto
    {
        return $this->actionConfigDto;
    }

    public function setActionsConfig(ActionConfigDto $actionConfig): void
    {
        $this->actionConfigDto = $actionConfig;
    }

    public function getFiltersConfig(): FilterConfigDto
    {
        return $this->filters;
    }

    public function setFiltersConfig(FilterConfigDto $filterConfig): void
    {
        $this->filters = $filterConfig;
    }

    public function getContentWidth(): ?string
    {
        return $this->contentWidth;
    }

    public function setContentWidth(string $contentWidth): void
    {
        $this->contentWidth = $contentWidth;
    }

    public function getSidebarWidth(): ?string
    {
        return $this->sidebarWidth;
    }

    public function setSidebarWidth(string $sidebarWidth): void
    {
        $this->sidebarWidth = $sidebarWidth;
    }
}
