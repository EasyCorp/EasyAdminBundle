<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Configuration;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class CrudConfig
{
    use CommonFormatConfigTrait;
    use CommonTemplateConfigTrait;

    private $entityFqcn;
    private $labelInSingular = 'Undefined';
    private $labelInPlural = 'Undefined';

    public static function new(): self
    {
        return new self();
    }

    public function getEntityClass(): string
    {
        return $this->entityFqcn;
    }

    public function getLabelInSingular(): string
    {
        return $this->labelInSingular;
    }

    public function getLabelInPlural(): string
    {
        return $this->labelInPlural;
    }

    public function setEntityClass(string $entityFqcn): self
    {
        $this->entityFqcn = $entityFqcn;

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
}
