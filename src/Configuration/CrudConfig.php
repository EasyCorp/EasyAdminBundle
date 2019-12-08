<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudConfig
{
    use CommonFormatConfigTrait;
    use CommonTemplateConfigTrait;
    use CommonFormThemeConfigTrait;

    private $entityFqcn;
    private $labelInSingular = 'Undefined';
    private $labelInPlural = 'Undefined';

    public static function new(): self
    {
        return new self();
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

        return new CrudDto($this->entityFqcn, $this->labelInSingular, $this->labelInPlural, $this->dateFormat, $this->timeFormat, $this->dateTimeFormat, $this->dateIntervalFormat, $this->numberFormat, $this->customTemplates, $this->defaultTemplates, $this->formThemes);
    }
}
