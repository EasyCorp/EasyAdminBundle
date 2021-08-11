<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterDataDto
{
    private $index;
    private $entityAlias;
    /** @var FilterDto */
    private $filterDto;
    private $comparison;
    private $value;
    private $value2;
    private $valueMetaData;
    private $valuePrimaryKeyName;
    private $valuePrimaryKeyValue;

    private function __construct()
    {
    }

    public static function new(int $index, FilterDto $filterDto, string $entityAlias, array $formData, ?ClassMetadata $valueMetaData = null): self
    {
        $filterData = new self();
        $filterData->index = $index;
        $filterData->filterDto = $filterDto;
        $filterData->entityAlias = $entityAlias;
        $filterData->comparison = $formData['comparison'];
        $filterData->value = $formData['value'];
        $filterData->value2 = $formData['value2'] ?? null;
        $filterData->valueMetaData = $valueMetaData;
        if ($valueMetaData) {
            $filterData->valuePrimaryKeyName = $valueMetaData->getIdentifierFieldNames()[0];
        }

        return $filterData;
    }

    public function getEntityAlias(): string
    {
        return $this->entityAlias;
    }

    public function getProperty(): string
    {
        return $this->filterDto->getProperty();
    }

    public function getFormTypeOption(string $optionName)
    {
        return $this->filterDto->getFormTypeOption($optionName);
    }

    public function getComparison(): string
    {
        return $this->comparison;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getValue2()
    {
        return $this->value2;
    }

    public function getPrimaryKeyValue()
    {
        if (null !== $this->valuePrimaryKeyValue) {
            return $this->valuePrimaryKeyValue;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        $primaryKeyValue = $propertyAccessor->getValue($this->value, $this->valuePrimaryKeyName);

        return $this->valuePrimaryKeyValue = $primaryKeyValue;
    }

    public function getPrimaryKeyValueAsString(): string
    {
        return (string) $this->getPrimaryKeyValue();
    }

    public function getValueMetaData()
    {
        return $this->valueMetaData;
    }

    public function getParameterName(): string
    {
        return sprintf('%s_%d', str_replace('.', '_', $this->getProperty()), $this->index);
    }

    public function getParameter2Name(): string
    {
        return sprintf('%s_%d', str_replace('.', '_', $this->getProperty()), $this->index + 1);
    }
}
