<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

final class FilterDataDto
{
    private $index;
    private $entityAlias;
    private $property;
    private $comparison;
    private $value;
    private $value2;

    private function __construct()
    {
    }

    public static function new(int $index, string $property, string $entityAlias, array $formData): self
    {
        $filterData = new self();
        $filterData->index = $index;
        $filterData->entityAlias = $entityAlias;
        $filterData->property = $property;
        $filterData->comparison = $formData['comparison'];
        $filterData->value = $formData['value'];
        $filterData->value2 = $formData['value2'] ?? null;

        return $filterData;
    }

    public function getEntityAlias(): string
    {
        return $this->entityAlias;
    }

    public function getProperty(): string
    {
        return $this->property;
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

    public function getParameterName(): string
    {
        return sprintf('%s_%d', str_replace('.', '_', $this->property), $this->index);
    }

    public function getParameter2Name(): string
    {
        return sprintf('%s_%d', str_replace('.', '_', $this->property), $this->index + 1);
    }
}
