<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;


use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudDtoInterface
{
    public function getControllerFqcn(): ?string;

    public function setControllerFqcn(string $fqcn): void;

    public function getCurrentPage(): ?string;

    public function setPageName(?string $pageName): void;

    public function getFieldAssets(string $pageName): AssetsDtoInterface;

    public function setFieldAssets(AssetsDtoInterface $assets): void;

    public function getEntityFqcn(): string;

    public function setEntityFqcn(string $entityFqcn): void;

    public function getEntityLabelInSingular(
        $entityInstance = null,
        $pageName = null
    ): TranslatableInterface|string|null;

    /**
     * @param TranslatableInterface|string|callable $label
     */
    public function setEntityLabelInSingular($label): void;

    public function getEntityLabelInPlural($entityInstance = null, $pageName = null): TranslatableInterface|string|null;

    /**
     * @param TranslatableInterface|string|callable $label
     */
    public function setEntityLabelInPlural($label): void;

    public function getCustomPageTitle(
        ?string $pageName = null,
        $entityInstance = null,
        array $translationParameters = []
    ): ?TranslatableInterface;

    /**
     * @param TranslatableInterface|string|callable $pageTitle
     */
    public function setCustomPageTitle(string $pageName, $pageTitle): void;

    public function getDefaultPageTitle(
        ?string $pageName = null,
        $entityInstance = null,
        array $translationParameters = []
    ): ?TranslatableInterface;

    public function getHelpMessage(?string $pageName = null): TranslatableInterface|string;

    public function getHelpMessages(): array;

    public function setHelpMessage(string $pageName, TranslatableInterface|string $helpMessage): void;

    public function getDatePattern(): ?string;

    public function setDatePattern(?string $format): void;

    public function getTimePattern(): ?string;

    public function setTimePattern(?string $format): void;

    public function getDateTimePattern(): array;

    public function setDateTimePattern(
        string $dateFormatOrPattern,
        string $timeFormat = DateTimeField::FORMAT_NONE
    ): void;

    public function getDateIntervalFormat(): string;

    public function setDateIntervalFormat(string $format): void;

    public function getTimezone(): ?string;

    public function setTimezone(string $timezoneId): void;

    public function getNumberFormat(): ?string;

    public function setNumberFormat(string $numberFormat): void;

    public function getDefaultSort(): array;

    public function setDefaultSort(array $defaultSort): void;

    public function getSearchFields(): ?array;

    public function setSearchFields(?array $searchFields): void;

    public function autofocusSearch(): bool;

    public function setAutofocusSearch(bool $autofocusSearch): void;

    public function isSearchEnabled(): bool;

    public function showEntityActionsAsDropdown(): bool;

    public function setShowEntityActionsAsDropdown(bool $showAsDropdown): void;

    public function getPaginator(): PaginatorDtoInterface;

    public function setPaginator(PaginatorDtoInterface $paginatorDto): void;

    public function getOverriddenTemplates(): array;

    public function overrideTemplate(string $templateName, string $templatePath): void;

    public function getFormThemes(): array;

    public function addFormTheme(string $formThemePath): void;

    public function setFormThemes(array $formThemes): void;

    public function getNewFormOptions(): KeyValueStore;

    public function getEditFormOptions(): KeyValueStore;

    public function setNewFormOptions(KeyValueStore $formOptions): void;

    public function setEditFormOptions(KeyValueStore $formOptions): void;

    public function getEntityPermission(): ?string;

    public function setEntityPermission(string $entityPermission): void;

    public function getCurrentAction(): string;

    public function setCurrentAction(string $actionName): void;

    public function getActionsConfig(): ActionConfigDtoInterface;

    public function setActionsConfig(ActionConfigDtoInterface $actionConfig): void;

    public function getFiltersConfig(): FilterConfigDtoInterface;

    public function setFiltersConfig(FilterConfigDtoInterface $filterConfig): void;

    public function getContentWidth(): ?string;

    public function setContentWidth(string $contentWidth): void;

    public function getSidebarWidth(): ?string;

    public function setSidebarWidth(string $sidebarWidth): void;

    public function areNullValuesHidden(): bool;

    public function hideNullValues(bool $hide): void;
}
