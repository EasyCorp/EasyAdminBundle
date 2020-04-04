<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait FilterTrait
{
    /** @var FilterDto */
    private $dto;

    private function __construct()
    {
        $dto = new FilterDto();
        $dto->setApplyCallable(function (QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto) {
            return $this->apply($queryBuilder, $filterDataDto, $fieldDto, $entityDto);
        });

        $this->dto = $dto;
    }

    public function __toString(): string
    {
        return $this->dto->getProperty();
    }

    public function setFilterFqcn(string $fqcn): self
    {
        $this->dto->setFqcn($fqcn);

        return $this;
    }

    public function setProperty(string $propertyName): self
    {
        $this->dto->setProperty($propertyName);

        return $this;
    }

    public function setLabel($label): self
    {
        $this->dto->setLabel($label);

        return $this;
    }

    public function setFormType(string $formType): self
    {
        $this->dto->setFormType($formType);

        return $this;
    }

    public function setFormTypeOptions(array $options): self
    {
        $this->dto->setFormTypeOptions($options);

        return $this;
    }

    public function setFormTypeOption(string $optionName, $optionValue): self
    {
        $this->dto->setFormTypeOption($optionName, $optionValue);

        return $this;
    }

    public function getAsDto(): FilterDto
    {
        return $this->dto;
    }
}
