<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudConfig
{
    private $entityFqcn;
    private $entityLabelInSingular = 'Undefined';
    private $entityLabelInPlural = 'Undefined';
    private $dateFormat = 'Y-m-d';
    private $timeFormat = 'H:i:s';
    private $dateTimeFormat = 'F j, Y H:i';
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat;
    private $formThemes = ['@EasyAdmin/crud/form_theme.html.twig'];
    /**
     * @internal
     * @var TemplateDtoCollection
     */
    private $overriddenTemplates;

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

    public function setDateFormat(string $format): self
    {
        $this->dateFormat = $format;

        return $this;
    }

    public function setTimeFormat(string $format): self
    {
        $this->timeFormat = $format;

        return $this;
    }

    public function setDateTimeFormat(string $format): self
    {
        $this->dateTimeFormat = $format;

        return $this;
    }

    public function setDateIntervalFormat(string $format): self
    {
        $this->dateIntervalFormat = $format;

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
        if (!in_array($templateName, $validTemplateNames)) {
            throw new \InvalidArgumentException(sprintf('The "%s" template is not defined in EasyAdmin. Use one of these allowed template names: %s', $templateName, implode(', ', $validTemplateNames)));
        }

        $this->overriddenTemplates->setTemplate($templateName, $templatePath);

        return $this;
    }

    /**
     * Format: ['templateName' => 'templatePath', ...]
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
                throw new \InvalidArgumentException(sprintf('All form theme paths must be strings, but "%s" was provided in "%s"', gettype($path), (string) $path));
            }
        }

        $this->formThemes = $themePaths;

        return $this;
    }

    public function getAsDto(bool $validateProperties = true): CrudDto
    {
        if ($validateProperties) {
            $this->validate();
        }

        if (null === $this->entityLabelInSingular) {
            $this->entityLabelInSingular = (new \ReflectionClass($this->entityFqcn))->getName();
        }

        if (null === $this->entityLabelInPlural) {
            $this->entityLabelInPlural = $this->entityLabelInSingular;
        }

        return new CrudDto($this->entityFqcn, $this->entityLabelInSingular, $this->entityLabelInPlural, $this->dateFormat, $this->timeFormat, $this->dateTimeFormat, $this->dateIntervalFormat, $this->numberFormat, $this->overriddenTemplates, $this->formThemes);
    }

    private function validate(): void
    {
        if (null === $this->entityFqcn) {
            throw new \RuntimeException(sprintf('One of your CrudControllers doesn\'t define the FQCN of its related Doctrine entity. Did you forget to call the "setEntityFqcn()" method on the "CrudConfig" object?'));
        }
    }
}
