<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

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

    /** @var CrudDto */
    private $dto;

    private $paginatorPageSize = 15;
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

    public function setEntityLabelInSingular(string $label): self
    {
        $this->dto->setEntityLabelInSingular($label);

        return $this;
    }

    public function setEntityLabelInPlural(string $label): self
    {
        $this->dto->setEntityLabelInPlural($label);

        return $this;
    }

    public function setPageTitle(string $pageName, string $title): self
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
        if ('none' === $formatOrPattern || '' === trim($formatOrPattern)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "none" or an empty string. Define either the date format or the datetime Intl pattern.', __METHOD__));
        }

        if (!\in_array($formatOrPattern, DateTimeField::VALID_DATE_FORMATS, true)) {
            $this->dto->setDateTimePattern($formatOrPattern);
            $this->dto->setDateFormat(null);
        } else {
            $this->dto->setDateTimePattern('');
            $this->dto->setDateFormat($formatOrPattern);
        }

        return $this;
    }

    /**
     * @param string $formatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     */
    public function setTimeFormat(string $formatOrPattern): self
    {
        if ('none' === $formatOrPattern || '' === trim($formatOrPattern)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "none" or an empty string. Define either the time format or the datetime Intl pattern.', __METHOD__));
        }

        if (!\in_array($formatOrPattern, DateTimeField::VALID_DATE_FORMATS, true)) {
            $this->dto->setDateTimePattern($formatOrPattern);
            $this->dto->setTimeFormat(null);
        } else {
            $this->dto->setDateTimePattern('');
            $this->dto->setTimeFormat($formatOrPattern);
        }

        return $this;
    }

    /**
     * @param string $dateFormatOrPattern A format name ('none', 'short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     * @param string $timeFormat          A format name ('none', 'short', 'medium', 'long', 'full')
     */
    public function setDateTimeFormat(string $dateFormatOrPattern, string $timeFormat = 'none'): self
    {
        if ('' === trim($dateFormatOrPattern)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be an empty string. Define either the date format or the datetime Intl pattern.', __METHOD__));
        }

        if ('none' === $dateFormatOrPattern && 'none' === $timeFormat) {
            throw new \InvalidArgumentException(sprintf('The values of the arguments of "%s()" cannot be "none" at the same time. Change any of them (or both).', __METHOD__));
        }

        $isDatePattern = !\in_array($dateFormatOrPattern, DateTimeField::VALID_DATE_FORMATS, true);

        if ($isDatePattern && 'none' !== $timeFormat) {
            throw new \InvalidArgumentException(sprintf('When the first argument of "%s()" is a datetime pattern, you cannot set the time format in the second argument (define the time format as part of the datetime pattern).', __METHOD__));
        }

        if (!$isDatePattern && !\in_array($timeFormat, DateTimeField::VALID_DATE_FORMATS, true)) {
            throw new \InvalidArgumentException(sprintf('The value of the time format can only be one of the following: %s (but "%s" was given).', implode(', ', DateTimeField::VALID_DATE_FORMATS), $timeFormat));
        }

        if ($isDatePattern) {
            $this->dto->setDateTimePattern($dateFormatOrPattern);
            $this->dto->setDateFormat(null);
            $this->dto->setTimeFormat(null);
        } else {
            $this->dto->setDateTimePattern('');
            $this->dto->setDateFormat($dateFormatOrPattern);
            $this->dto->setTimeFormat($timeFormat);
        }

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
            if (!\in_array($sortOrder, ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException(sprintf('The sort order can be only "ASC" or "DESC", "%s" given.', $sortOrder));
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
        $this->dto->setShowEntityActionsAsDropdown($showAsDropdown);

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
            throw new \InvalidArgumentException(sprintf('The minimum value of paginator page size is 1.'));
        }

        $this->paginatorPageSize = $maxResultsPerPage;

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
        // custom form themes are added first to give them more priority
        $formThemes = $this->dto->getFormThemes();
        array_unshift($formThemes, $themePath);
        $this->dto->setFormThemes($formThemes);

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

    public function getAsDto(): CrudDto
    {
        $this->dto->setPaginator(new PaginatorDto($this->paginatorPageSize, $this->paginatorFetchJoinCollection, $this->paginatorUseOutputWalkers));

        return $this->dto;
    }

    private function getValidPageNames(): array
    {
        return [self::PAGE_DETAIL, self::PAGE_EDIT, self::PAGE_INDEX, self::PAGE_NEW];
    }
}
