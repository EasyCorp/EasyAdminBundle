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
    private $labelInSingular = 'Undefined';
    private $labelInPlural = 'Undefined';
    private $dateFormat = 'Y-m-d';
    private $timeFormat = 'H:i:s';
    private $dateTimeFormat = 'F j, Y H:i';
    private $dateIntervalFormat = '%%y Year(s) %%m Month(s) %%d Day(s)';
    private $numberFormat;
    /** @var TemplateDtoCollection */
    private $customTemplates;
    private $formThemes = ['@EasyAdmin/form_theme.html.twig'];

    public static function new(): self
    {
        $config = new self();
        $config->customTemplates = TemplateDtoCollection::new();

        return $config;
    }

    public function setEntityFqcn(string $fqcn): self
    {
        $this->entityFqcn = $fqcn;

        return $this;
    }

    public function setLabelInSingular(string $label): self
    {
        $this->labelInSingular = $label;

        return $this;
    }

    public function setLabelInPlural(string $label): self
    {
        $this->labelInPlural = $label;

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

    /**
     * Used to override the default template used to render a specific backend part.
     */
    public function setCustomTemplate(string $templateName, string $templatePath): self
    {
        $validTemplateNames = TemplateRegistry::getTemplateNames();
        if (!array_key_exists($templateName, $validTemplateNames)) {
            throw new \InvalidArgumentException(sprintf('The "%s" template is not defined in EasyAdmin. Use one of these allowed template names: %s', $templateName, implode(', ', $validTemplateNames)));
        }

        $this->customTemplates->setTemplate($templateName, $templatePath);

        return $this;
    }

    /**
     * It allows to override more than one template at the same time.
     * Format: ['templateName' => 'templatePath', ...]
     */
    public function setCustomTemplates(array $templateNamesAndPaths): self
    {
        foreach ($templateNamesAndPaths as $templateName => $templatePath) {
            $this->setCustomTemplate($templateName, $templatePath);
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

        if (null === $this->labelInSingular) {
            $this->labelInSingular = (new \ReflectionClass($this->entityFqcn))->getName();
        }

        if (null === $this->labelInPlural) {
            $this->labelInPlural = $this->labelInSingular;
        }

        return new CrudDto($this->entityFqcn, $this->labelInSingular, $this->labelInPlural, $this->dateFormat, $this->timeFormat, $this->dateTimeFormat, $this->dateIntervalFormat, $this->numberFormat, $this->customTemplates, $this->formThemes);
    }

    private function validate(): void
    {
        if (null === $this->entityFqcn) {
            throw new \RuntimeException(sprintf('One of your CrudControllers doesn\'t define the FQCN of its related Doctrine entity. Did you forget to call the "setEntityFqcn()" method on the "CrudConfig" object?'));
        }
    }
}
