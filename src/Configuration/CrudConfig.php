<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Property\DateTimeProperty;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudConfig
{
    private $entityFqcn;
    private $entityLabelInSingular;
    private $entityLabelInPlural;
    private $dateFormat = 'medium';
    private $timeFormat = 'medium';
    private $dateTimePattern = '';
    private $timezone;
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat;
    private $formThemes = ['@EasyAdmin/crud/form_theme.html.twig'];
    /**
     * @internal
     *
     * @var TemplateDtoCollection
     */
    private $overriddenTemplates;
    private $disabledActions = [];

    public static function new(): self
    {
        $config = new self();
        $config->overriddenTemplates = TemplateDtoCollection::new();

        return $config;
    }

    public function setEntityFqcn(string $fqcn): self
    {
        $this->entityFqcn = $fqcn;

        return $this;
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

    /**
     * @param string $formatOrPattern A format name ('short', 'medium', 'long', 'full') or a valid ICU Datetime Pattern (see http://userguide.icu-project.org/formatparse/datetime)
     */
    public function setDateFormat(string $formatOrPattern): self
    {
        if ('' === trim($formatOrPattern) || 'none' === $formatOrPattern) {
            throw new \InvalidArgumentException(sprintf('The first argument of the "%s()" method cannot be "none" or an empty string. Define either the date format or the datetime Intl pattern.', __METHOD__));
        }

        if (!\in_array($formatOrPattern, DateTimeProperty::VALID_DATE_FORMATS, true)) {
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

        if (!\in_array($formatOrPattern, DateTimeProperty::VALID_DATE_FORMATS, true)) {
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

        $isDatePattern = !\in_array($dateFormatOrPattern, DateTimeProperty::VALID_DATE_FORMATS, true);

        if ($isDatePattern && 'none' !== $timeFormat) {
            throw new \InvalidArgumentException(sprintf('When the first argument of "%s()" is a datetime pattern, you cannot set the time format in the second argument (define the time format as part of the datetime pattern).', __METHOD__));
        }

        if (!$isDatePattern && !\in_array($timeFormat, DateTimeProperty::VALID_DATE_FORMATS, true)) {
            throw new \InvalidArgumentException(sprintf('The value of the time format can only be one of the following: %s (but "%s" was given).', implode(', ', DateTimeProperty::VALID_DATE_FORMATS), $timeFormat));
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

    public function disableActions(string ...$actionNames): self
    {
        foreach ($actionNames as $actionName) {
            $this->disabledActions[] = $actionName;
        }

        return $this;
    }

    public function getAsDto(bool $validateProperties = true): CrudDto
    {
        if ($validateProperties) {
            $this->validate();
        }

        if (null === $this->entityLabelInSingular) {
            $entityClassName = basename(str_replace('\\', '/', $this->entityFqcn));
            $this->entityLabelInSingular = empty($entityClassName) ? 'Undefined' : $entityClassName;
        }

        if (null === $this->entityLabelInPlural) {
            $this->entityLabelInPlural = $this->entityLabelInSingular;
        }

        return new CrudDto($this->entityFqcn, $this->entityLabelInSingular, $this->entityLabelInPlural, $this->dateFormat, $this->timeFormat, $this->dateTimePattern, $this->dateIntervalFormat, $this->timezone, $this->numberFormat, $this->overriddenTemplates, $this->formThemes, $this->disabledActions);
    }

    private function validate(): void
    {
        if (null === $this->entityFqcn) {
            throw new \RuntimeException(sprintf('One of your CrudControllers doesn\'t define the FQCN of its related Doctrine entity. Did you forget to call the "setEntityFqcn()" method on the "CrudConfig" object?'));
        }
    }
}
