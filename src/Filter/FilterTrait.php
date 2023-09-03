<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDtoInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDtoInterface;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
trait FilterTrait
{
    private FilterDto $dto;

    private function __construct()
    {
        $dto = new FilterDto();
        $dto->setApplyCallable(
            fn(
                QueryBuilder $queryBuilder,
                FilterDataDtoInterface $filterDataDto,
                ?FieldDtoInterface $fieldDto,
                EntityDtoInterface $entityDto
            ) => $this->apply($queryBuilder, $filterDataDto, $fieldDto, $entityDto)
        );

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

    public function setFormTypeOption(string $optionName, mixed $optionValue): self
    {
        $this->dto->setFormTypeOption($optionName, $optionValue);

        return $this;
    }

    public function getAsDto(): FilterDtoInterface
    {
        return $this->dto;
    }
}
