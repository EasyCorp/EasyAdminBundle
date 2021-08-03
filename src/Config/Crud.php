<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\SortOrder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Crud
{
    public const PAGE_DETAIL = 'detail';
    public const PAGE_EDIT = 'edit';
    public const PAGE_INDEX = 'index';
    public const PAGE_NEW = 'new';
    public const LAYOUT_CONTENT_DEFAULT = 'normal';
    public const LAYOUT_CONTENT_FULL = 'full';
    public const LAYOUT_SIDEBAR_DEFAULT = 'normal';
    public const LAYOUT_SIDEBAR_COMPACT = 'compact';

    /** @var CrudDto */
    private $dto;

    private $paginatorPageSize = 20;
    private $paginatorRangeSize = 3;
    private $paginatorFetchJoinCollection = true;
    private $paginatorUseOutputWalkers;

    private function __construct(CrudDto $crudDto)
    {
        $this->dto = $crudDto;
    }

    public static function new(): self
    {
        $dto = new CrudDto();

        return new self($dto);
    }

    /**
     * @param string|callable $label The callable signature is: fn ($entityInstance, $pageName): string
     */
    public function setEntityLabelInSingular($label): self
    {
        $this->dto->setEntityLabelInSingular($label);

        return $this;
    }

    /**
     * @param string|callable $label The callable signature is: fn ($entityInstance, $pageName): string
     */
    public function setEntityLabelInPlural($label): self
    {
        $this->dto->setEntityLabelInPlural($label);

        return $this;
    }

    /**
     * @param string|callable $title The callable signature is: fn ($entityInstance): string
     */
    public function setPageTitle(string $pageName, $title): self
    {
        if (!\in_array($pageName, $this->getValidPageNames(), true)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method must be one of these valid page names: %s ("%s" given).', __METHOD__, implode(', ', $this->getValidPageNames()), $pageName));
        }

        $this->dto->setCustomPageTitle($pageName, $title);

        return $this;
    }

    public function setHelp(string $pageName, string $helpMessage): self
    {
        if (!\in_array($pageName, $this->getValidPageNames(), true)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method must be one of these valid page names: %s ("%s" given).', __METHOD__, implode(', ', $this->getValidPageNames()), $pageName));
        }

        $this->dto->setHelpMessage($pageName, $helpMessage);

        return $this;
    }

    /**
     * @param string $formatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     */
    public function setDateFormat(string $formatOrPattern): self
    {
        if (DateTimeField::FORMAT_NONE === $formatOrPattern || '' === trim($formatOrPattern)) {
            $validDateFormatsWithoutNone = array_filter(DateTimeField::VALID_DATE_FORMATS, static function ($format) {
                return DateTimeField::FORMAT_NONE !== $format;
            });

            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "%s" or an empty string. Use either the special date formats (%s) or a datetime Intl pattern.', __METHOD__, DateTimeField::FORMAT_NONE, implode(', ', $validDateFormatsWithoutNone)));
        }

        $datePattern = DateTimeField::INTL_DATE_PATTERNS[$formatOrPattern] ?? $formatOrPattern;
        $this->dto->setDatePattern($datePattern);

        return $this;
    }

    /**
     * @param string $formatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     */
    public function setTimeFormat(string $formatOrPattern): self
    {
        if (DateTimeField::FORMAT_NONE === $formatOrPattern || '' === trim($formatOrPattern)) {
            $validTimeFormatsWithoutNone = array_filter(DateTimeField::VALID_DATE_FORMATS, static function ($format) {
                return DateTimeField::FORMAT_NONE !== $format;
            });

            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "%s" or an empty string. Use either the special time formats (%s) or a datetime Intl pattern.', __METHOD__, DateTimeField::FORMAT_NONE, implode(', ', $validTimeFormatsWithoutNone)));
        }

        $timePattern = DateTimeField::INTL_TIME_PATTERNS[$formatOrPattern] ?? $formatOrPattern;
        $this->dto->setTimePattern($timePattern);

        return $this;
    }

    /**
     * @param string $dateFormatOrPattern A format name ('none', 'short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     * @param string $timeFormat          A format name ('none', 'short', 'medium', 'long', 'full')
     */
    public function setDateTimeFormat(string $dateFormatOrPattern, string $timeFormat = DateTimeField::FORMAT_NONE): self
    {
        if ('' === trim($dateFormatOrPattern)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be an empty string. Use either a date format (%s) or a datetime Intl pattern.', __METHOD__, implode(', ', DateTimeField::VALID_DATE_FORMATS)));
        }

        $datePatternIsEmpty = DateTimeField::FORMAT_NONE === $dateFormatOrPattern || '' === trim($dateFormatOrPattern);
        $timePatternIsEmpty = DateTimeField::FORMAT_NONE === $timeFormat || '' === trim($timeFormat);
        if ($datePatternIsEmpty && $timePatternIsEmpty) {
            throw new \InvalidArgumentException(sprintf('The values of the arguments of "%s()" cannot be "%s" or an empty string at the same time. Change any of them (or both).', __METHOD__, DateTimeField::FORMAT_NONE));
        }

        // when date format/pattern is none and time format is a pattern,
        // silently turn them into a datetime pattern
        if (DateTimeField::FORMAT_NONE === $dateFormatOrPattern && !\in_array($timeFormat, DateTimeField::VALID_DATE_FORMATS, true)) {
            $dateFormatOrPattern = $timeFormat;
            $timeFormat = DateTimeField::FORMAT_NONE;
        }

        $isDatePattern = !\in_array($dateFormatOrPattern, DateTimeField::VALID_DATE_FORMATS, true);

        if ($isDatePattern && DateTimeField::FORMAT_NONE !== $timeFormat) {
            throw new \InvalidArgumentException(sprintf('When the first argument of "%s()" is a datetime pattern, you cannot set the time format in the second argument (define the time format inside the datetime pattern).', __METHOD__));
        }

        if (!$isDatePattern && !\in_array($timeFormat, DateTimeField::VALID_DATE_FORMATS, true)) {
            throw new \InvalidArgumentException(sprintf('The value of the time format can only be one of the following: %s (but "%s" was given).', implode(', ', DateTimeField::VALID_DATE_FORMATS), $timeFormat));
        }

        $this->dto->setDateTimePattern($dateFormatOrPattern, $timeFormat);

        return $this;
    }

    public function setDateIntervalFormat(string $format): self
    {
        $this->dto->setDateIntervalFormat($format);

        return $this;
    }

    public function setTimezone(string $timezoneId): self
    {
        if (!\in_array($timezoneId, timezone_identifiers_list(), true)) {
            throw new \InvalidArgumentException(sprintf('The "%s" timezone is not a valid PHP timezone ID. Use any of the values listed at https://www.php.net/manual/en/timezones.php', $timezoneId));
        }

        $this->dto->setTimezone($timezoneId);

        return $this;
    }

    public function setNumberFormat(string $format): self
    {
        $this->dto->setNumberFormat($format);

        return $this;
    }

    /**
     * @param array $sortFieldsAndOrder ['fieldName' => 'ASC|DESC', ...]
     */
    public function setDefaultSort(array $sortFieldsAndOrder): self
    {
        $sortFieldsAndOrder = array_map('strtoupper', $sortFieldsAndOrder);
        foreach ($sortFieldsAndOrder as $sortField => $sortOrder) {
            if (!\in_array($sortOrder, [SortOrder::ASC, SortOrder::DESC], true)) {
                throw new \InvalidArgumentException(sprintf('The sort order can be only "%s" or "%s", "%s" given.', SortOrder::ASC, SortOrder::DESC, $sortOrder));
            }

            if (!\is_string($sortField)) {
                throw new \InvalidArgumentException(sprintf('The keys of the array that defines the default sort must be strings with the field names, but the given "%s" value is a "%s".', $sortField, \gettype($sortField)));
            }
        }

        $this->dto->setDefaultSort($sortFieldsAndOrder);

        return $this;
    }

    public function setSearchFields(?array $fieldNames): self
    {
        $this->dto->setSearchFields($fieldNames);

        return $this;
    }

    public function showEntityActionsAsDropdown(bool $showAsDropdown = true): self
    {
        trigger_deprecation('easycorp/easyadmin-bundle', '3.5.0', 'The "%s" method is deprecated because the default behavior changed to render entity actions as dropdown. Use "showEntityActionsInlined()" method if you want to revert this change.', __METHOD__);

        $this->dto->setShowEntityActionsAsDropdown($showAsDropdown);

        return $this;
    }

    public function showEntityActionsInlined(bool $showInlined = true): self
    {
        $this->dto->setShowEntityActionsAsDropdown(!$showInlined);

        return $this;
    }

    public function setFilters(?array $filters): self
    {
        $this->dto->setFiltersConfig($filters);

        return $this;
    }

    public function setPaginatorPageSize(int $maxResultsPerPage): self
    {
        if ($maxResultsPerPage < 1) {
            throw new \InvalidArgumentException('The minimum value of paginator page size is 1.');
        }

        $this->paginatorPageSize = $maxResultsPerPage;

        return $this;
    }

    public function setPaginatorRangeSize(int $maxPagesOnEachSide): self
    {
        if ($maxPagesOnEachSide < 0) {
            throw new \InvalidArgumentException('The minimum value of paginator range size is 0.');
        }

        $this->paginatorRangeSize = $maxPagesOnEachSide;

        return $this;
    }

    public function setPaginatorFetchJoinCollection(bool $fetchJoinCollection): self
    {
        $this->paginatorFetchJoinCollection = $fetchJoinCollection;

        return $this;
    }

    public function setPaginatorUseOutputWalkers(bool $useOutputWalkers): self
    {
        $this->paginatorUseOutputWalkers = $useOutputWalkers;

        return $this;
    }

    public function overrideTemplate(string $templateName, string $templatePath): self
    {
        $this->dto->overrideTemplate($templateName, $templatePath);

        return $this;
    }

    /**
     * Format: ['templateName' => 'templatePath', ...].
     */
    public function overrideTemplates(array $templateNamesAndPaths): self
    {
        foreach ($templateNamesAndPaths as $templateName => $templatePath) {
            $this->overrideTemplate($templateName, $templatePath);
        }

        return $this;
    }

    public function addFormTheme(string $themePath): self
    {
        // custom form themes are added last to give them more priority
        $this->dto->setFormThemes(array_merge($this->dto->getFormThemes(), [$themePath]));

        return $this;
    }

    public function setFormThemes(array $themePaths): self
    {
        foreach ($themePaths as $path) {
            if (!\is_string($path)) {
                throw new \InvalidArgumentException(sprintf('All form theme paths passed to the "%s" method must be strings, but at least one of those values is of type "%s".', __METHOD__, \gettype($path)));
            }
        }

        $this->dto->setFormThemes($themePaths);

        return $this;
    }

    public function setFormOptions(array $newFormOptions, array $editFormOptions = null): self
    {
        $this->dto->setNewFormOptions(KeyValueStore::new($newFormOptions));
        $this->dto->setEditFormOptions(KeyValueStore::new($editFormOptions ?? $newFormOptions));

        return $this;
    }

    public function setEntityPermission(string $permission): self
    {
        $this->dto->setEntityPermission($permission);

        return $this;
    }

    public function renderContentMaximized(bool $maximized = true): self
    {
        $this->dto->setContentWidth($maximized ? self::LAYOUT_CONTENT_FULL : self::LAYOUT_CONTENT_DEFAULT);

        return $this;
    }

    public function renderSidebarMinimized(bool $minimized = true): self
    {
        $this->dto->setSidebarWidth($minimized ? self::LAYOUT_SIDEBAR_COMPACT : self::LAYOUT_SIDEBAR_DEFAULT);

        return $this;
    }

    public function getAsDto(): CrudDto
    {
        $this->dto->setPaginator(new PaginatorDto($this->paginatorPageSize, $this->paginatorRangeSize, 1, $this->paginatorFetchJoinCollection, $this->paginatorUseOutputWalkers));

        return $this->dto;
    }

    private function getValidPageNames(): array
    {
        return [self::PAGE_DETAIL, self::PAGE_EDIT, self::PAGE_INDEX, self::PAGE_NEW];
    }
}
