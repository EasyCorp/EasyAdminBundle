<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\ORM\QueryBuilder;

final class FilterDto
{
    private $fqcn;
    private $formType;
    private $formTypeOptions;
    private $propertyName;
    private $label;
    private $applyCallable;

    public function __construct()
    {
        $this->formTypeOptions = [];
    }

    public function getFqcn(): ?string
    {
        return $this->fqcn;
    }

    public function setFqcn(string $fqcn): void
    {
        $this->fqcn = $fqcn;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions;
    }

    public function setFormTypeOptions(array $formTypeOptions): void
    {
        $this->formTypeOptions = $formTypeOptions;
    }

    public function setFormTypeOption(string $optionName, $optionValue): void
    {
        $this->formTypeOptions[$optionName] = $optionValue;
    }

    public function setFormType(string $formType): void
    {
        $this->formType = $formType;
    }

    public function getProperty(): string
    {
        return $this->propertyName;
    }

    public function setProperty(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * @return string|null|false
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string|null|false $label
     */
    public function setLabel($label): void
    {
        $this->label = $label;
    }

    public function setApplyCallable(callable $callable): void
    {
        $this->applyCallable = $callable;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto): void
    {
        call_user_func($this->applyCallable, $queryBuilder, $filterDataDto);
    }
}
