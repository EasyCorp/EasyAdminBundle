<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Config;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\PaginatorDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Registry\TemplateRegistry;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Crud
{
    public const PAGE_DETAIL = 'detail';
    public const PAGE_EDIT = 'edit';
    public const PAGE_INDEX = 'index';
    public const PAGE_NEW = 'new';

    private $entityLabelInSingular;
    private $entityLabelInPlural;
    private $pageTitles = [Action::DETAIL => null, Action::EDIT => null, Action::INDEX => null, Action::NEW => null];
    private $helpMessages = [Action::DETAIL => null, Action::EDIT => null, Action::INDEX => null, Action::NEW => null];
    private $dateFormat = 'medium';
    private $timeFormat = 'medium';
    private $dateTimePattern = '';
    private $timezone;
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat;
    private $defaultSort = [];
    private $searchFields = [];
    private $showEntityActionsAsDropdown = false;
    private $filters;
    private $paginatorPageSize = 15;
    private $paginatorFetchJoinCollection = true;
    private $paginatorUseOutputWalkers;
    private $formThemes = ['@EasyAdmin/crud/form_theme.html.twig'];
    private $formOptions = [];
    private $entityPermission;
    /**
     * @internal
     *
     * @var TemplateDtoCollection
     */
    private $overriddenTemplates;

    public static function new(): self
    {
        $config = new self();
        $config->overriddenTemplates = TemplateDtoCollection::new();

        return $config;
    }

    public function setEntityLabelInSingular(string $label): self
    {
        $this->entityLabelInSingular = $label;

        return $this;
    }

    public function setEntityLabelInPlural(string $label): self
    {
        $this->entityLabelInPlural = $label;

        return $this;
    }

    public function setPageTitle(string $pageName, string $title): self
    {
        if (!\array_key_exists($pageName, $this->pageTitles)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method must be one of these valid page names: %s ("%s" given).', __METHOD__, implode(', ', array_keys($this->pageTitles)), $pageName));
        }

        $this->pageTitles[$pageName] = $title;

        return $this;
    }

    public function setHelpMessage(string $pageName, string $helpMessage): self
    {
        if (!\array_key_exists($pageName, $this->helpMessages)) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method must be one of these valid page names: %s ("%s" given).', __METHOD__, implode(', ', array_keys($this->helpMessages)), $pageName));
        }

        $this->helpMessages[$pageName] = $helpMessage;

        return $this;
    }

    /**
     * @param string $formatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     */
    public function setDateFormat(string $formatOrPattern): self
    {
        if ('' === trim($formatOrPattern) || 'none' === $formatOrPattern) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "none" or an empty string. Define either the date format or the datetime Intl pattern.', __METHOD__));
        }

        if (!\in_array($formatOrPattern, DateTimeField::VALID_DATE_FORMATS, true)) {
            $this->dateTimePattern = $formatOrPattern;
            $this->dateFormat = null;
        } else {
            $this->dateTimePattern = '';
            $this->dateFormat = $formatOrPattern;
        }

        return $this;
    }

    /**
     * @param string $formatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     */
    public function setTimeFormat(string $formatOrPattern): self
    {
        if ('' === trim($formatOrPattern) || 'none' === $formatOrPattern) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "none" or an empty string. Define either the time format or the datetime Intl pattern.', __METHOD__));
        }

        if (!\in_array($formatOrPattern, DateTimeField::VALID_DATE_FORMATS, true)) {
            $this->dateTimePattern = $formatOrPattern;
            $this->timeFormat = null;
        } else {
            $this->dateTimePattern = '';
            $this->timeFormat = $formatOrPattern;
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
            $this->dateTimePattern = $dateFormatOrPattern;
            $this->dateFormat = null;
            $this->timeFormat = null;
        } else {
            $this->dateTimePattern = '';
            $this->dateFormat = $dateFormatOrPattern;
            $this->timeFormat = $timeFormat;
        }

        return $this;
    }

    public function setDateIntervalFormat(string $format): self
    {
        $this->dateIntervalFormat = $format;

        return $this;
    }

    public function setTimezone(string $timezoneId): self
    {
        if (!\in_array($timezoneId, timezone_identifiers_list())) {
            throw new \InvalidArgumentException(sprintf('The "%s" timezone is not a valid PHP timezone ID. Use any of the values listed at https://www.php.net/manual/en/timezones.php', $timezoneId));
        }

        $this->timezone = $timezoneId;

        return $this;
    }

    public function setNumberFormat(string $format): self
    {
        $this->numberFormat = $format;

        return $this;
    }

    /**
     * @param array $sortAndOrder ['fieldName' => 'ASC|DESC', ...]
     */
    public function setDefaultSort(array $sortAndOrder): self
    {
        $sortAndOrder = array_map('strtoupper', $sortAndOrder);
        foreach ($sortAndOrder as $sortField => $sortOrder) {
            if (!\in_array($sortOrder, ['ASC', 'DESC'])) {
                throw new \InvalidArgumentException(sprintf('The sort order can be only "ASC" or "DESC", "%s" given.', $sortOrder));
            }

            if (!\is_string($sortField)) {
                throw new \InvalidArgumentException(sprintf('The keys of the array that defines the default sort must be strings with the field names, but the given "%s" value is a "%s".', $sortField, \gettype($sortField)));
            }
        }

        $this->defaultSort = $sortAndOrder;

        return $this;
    }

    public function setSearchFields(?array $fieldNames): self
    {
        $this->searchFields = $fieldNames;

        return $this;
    }

    public function showEntityActionsAsDropdown(bool $showAsDropdown = true): self
    {
        $this->showEntityActionsAsDropdown = $showAsDropdown;

        return $this;
    }

    public function setFilters(?array $filters): self
    {
        $this->filters = $filters;

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
        $validTemplateNames = TemplateRegistry::getTemplateNames();
        if (!\in_array($templateName, $validTemplateNames)) {
            throw new \InvalidArgumentException(sprintf('The "%s" template is not defined in EasyAdmin. Use one of these allowed template names: %s', $templateName, implode(', ', $validTemplateNames)));
        }

        $this->overriddenTemplates->setTemplate($templateName, $templatePath);

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
        array_unshift($this->formThemes, $themePath);

        return $this;
    }

    public function setFormThemes(array $themePaths): self
    {
        foreach ($themePaths as $path) {
            if (!\is_string($path)) {
                throw new \InvalidArgumentException(sprintf('All form theme paths must be strings, but "%s" was provided in "%s"', \gettype($path), (string) $path));
            }
        }

        $this->formThemes = $themePaths;

        return $this;
    }

    public function setFormOptions(array $formOptions): self
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    public function setEntityPermission(string $permission): self
    {
        $this->entityPermission = $permission;

        return $this;
    }

    public function getAsDto(): CrudDto
    {
        return new CrudDto($this->entityLabelInSingular, $this->entityLabelInPlural, $this->pageTitles, $this->helpMessages, $this->dateFormat, $this->timeFormat, $this->dateTimePattern, $this->dateIntervalFormat, $this->timezone, $this->numberFormat, $this->defaultSort, $this->searchFields, $this->showEntityActionsAsDropdown, $this->filters, new PaginatorDto($this->paginatorPageSize, $this->paginatorFetchJoinCollection, $this->paginatorUseOutputWalkers), $this->overriddenTemplates, $this->formThemes, $this->formOptions, $this->entityPermission);
    }
}
