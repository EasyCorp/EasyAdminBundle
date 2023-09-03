<?php

declare(strict_types=1);

namespace EasyCorp\Bundle\EasyAdminBundle\Config;


use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
interface CrudInterface
{
    public const PAGE_DETAIL = 'detail';

    public const PAGE_EDIT = 'edit';

    public const PAGE_INDEX = 'index';

    public const PAGE_NEW = 'new';

    public const LAYOUT_CONTENT_DEFAULT = 'normal';

    public const LAYOUT_CONTENT_FULL = 'full';

    public const LAYOUT_SIDEBAR_DEFAULT = 'normal';

    public const LAYOUT_SIDEBAR_COMPACT = 'compact';

    /**
     * @param TranslatableInterface|string|callable $label The callable signature is: fn ($entityInstance, $pageName): string
     *
     * @psalm-param mixed $label
     */
    public function setEntityLabelInSingular($label): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    /**
     * @param TranslatableInterface|string|callable $label The callable signature is: fn ($entityInstance, $pageName): string
     *
     * @psalm-param mixed $label
     */
    public function setEntityLabelInPlural($label): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    /**
     * @param TranslatableInterface|string|callable $title The callable signature is: fn ($entityInstance): string
     *
     * @psalm-param mixed $title
     */
    public function setPageTitle(string $pageName, $title): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setHelp(
        string $pageName,
        TranslatableInterface|string $helpMessage
    ): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    /**
     * @param string $formatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
     */
    public function setDateFormat(string $formatOrPattern): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    /**
     * @param string $formatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
     */
    public function setTimeFormat(string $formatOrPattern): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    /**
     * @param string $dateFormatOrPattern A format name ('none', 'short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see https://unicode-org.github.io/icu/userguide/format_parse/datetime/)
     * @param string $timeFormat A format name ('none', 'short', 'medium', 'long', 'full')
     */
    public function setDateTimeFormat(
        string $dateFormatOrPattern,
        string $timeFormat = DateTimeField::FORMAT_NONE
    ): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setDateIntervalFormat(string $format): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setTimezone(string $timezoneId): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setNumberFormat(string $format): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    /**
     * @param array $sortFieldsAndOrder ['fieldName' => 'ASC|DESC', ...]
     */
    public function setDefaultSort(array $sortFieldsAndOrder): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setSearchFields(?array $fieldNames): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setAutofocusSearch(bool $autofocusSearch = true): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function showEntityActionsInlined(bool $showInlined = true): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setFilters(?FilterConfigDtoInterface $filters): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setPaginatorPageSize(int $maxResultsPerPage): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setPaginatorRangeSize(int $maxPagesOnEachSide): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setPaginatorFetchJoinCollection(bool $fetchJoinCollection
    ): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setPaginatorUseOutputWalkers(bool $useOutputWalkers): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function overrideTemplate(
        string $templateName,
        string $templatePath
    ): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    /**
     * Format: ['templateName' => 'templatePath', ...].
     */
    public function overrideTemplates(array $templateNamesAndPaths): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function addFormTheme(string $themePath): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setFormThemes(array $themePaths): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setFormOptions(
        array $newFormOptions,
        ?array $editFormOptions = null
    ): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function setEntityPermission(string $permission): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function renderContentMaximized(bool $maximized = true): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function renderSidebarMinimized(bool $minimized = true): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function hideNullValues(bool $hide = true): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

    public function getAsDto(): CrudDtoInterface;
}
