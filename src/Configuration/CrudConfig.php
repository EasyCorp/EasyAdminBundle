<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Collection\TemplateDtoCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudConfig
{
    use CommonFormatConfigTrait;
    use CommonFormThemeConfigTrait;

    private $entityFqcn;
    private $labelInSingular = 'Undefined';
    private $labelInPlural = 'Undefined';
    /** @var TemplateDtoCollection */
    private $customTemplates;

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

    public function getAsDto(): CrudDto
    {
        if (null === $this->entityFqcn) {
            throw new \RuntimeException(sprintf('One of your CrudControllers doesn\'t define the FQCN of its related Doctrine entity. Did you forget to call the "setEntityClass()" on the "CrudConfig" object?'));
        }

        if (null === $this->labelInSingular) {
            $this->labelInSingular = (new \ReflectionClass($this->entityFqcn))->getName();
        }

        if (null === $this->labelInPlural) {
            $this->labelInPlural = $this->labelInSingular;
        }

        return new CrudDto($this->entityFqcn, $this->labelInSingular, $this->labelInPlural, $this->dateFormat, $this->timeFormat, $this->dateTimeFormat, $this->dateIntervalFormat, $this->numberFormat, $this->customTemplates, $this->formThemes);
    }
}
