<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Dto;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterDataDto
{
    private int $index;
    /** @var array{entity_dto: EntityDto, entity_alias: string, property_name: string} */
    private array $resolvedProperty;
    private FilterDto $filterDto;
    /** @var string */
    private $comparison;
    private mixed $value;
    private mixed $value2;

    private function __construct()
    {
    }

    /**
     * @param array{comparison: string, value: mixed, value2?: mixed}                   $formData
     * @param array{entity_dto: EntityDto, entity_alias: string, property_name: string} $resolvedProperty
     */
    public static function new(int $index, FilterDto $filterDto, array $resolvedProperty, array $formData): self
    {
        $filterData = new self();
        $filterData->index = $index;
        $filterData->filterDto = $filterDto;
        $filterData->resolvedProperty = $resolvedProperty;
        $filterData->comparison = $formData['comparison'];
        $filterData->value = $formData['value'];
        $filterData->value2 = $formData['value2'] ?? null;

        return $filterData;
    }

    public function getEntityAlias(): string
    {
        return $this->resolvedProperty['entity_alias'];
    }

    public function getProperty(): string
    {
        return $this->resolvedProperty['property_name'];
    }

    public function getFormTypeOption(string $optionName): mixed
    {
        return $this->filterDto->getFormTypeOption($optionName);
    }

    public function getComparison(): string
    {
        return $this->comparison;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getValue2(): mixed
    {
        return $this->value2;
    }

    public function getParameterName(): string
    {
        return sprintf('%s_%d', str_replace('.', '_', $this->filterDto->getProperty()), $this->index);
    }

    public function getParameter2Name(): string
    {
        return sprintf('%s_%d', str_replace('.', '_', $this->filterDto->getProperty()), $this->index + 1);
    }
}
