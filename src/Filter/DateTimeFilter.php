<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use function Symfony\Component\String\u;

final class DateTimeFilter
{
    private $formType;
    private $formTypeOptions;
    private $property;
    private $label;

    private function __construct()
    {
    }

    public function __toString(): string
    {
        return $this->property;
    }

    public static function new(string $propertyName, $label = null): self
    {
        $filter = new self();
        $filter->formType = DateTimeFilterType::class;
        $filter->property = $propertyName;
        $filter->label = $label ?? u($propertyName)->title(true)->toString();

        return $filter;
    }

    public function getFormType(): string
    {
        return $this->formType;
    }

    public function getFormTypeOptions(): array
    {
        return $this->formTypeOptions ?? [];
    }

    public function setFormTypeOptions(array $options): self
    {
        $this->formTypeOptions = $options;

        return $this;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getLabel()
    {
        return $this->label;
    }



    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto)
    {
        $alias = $filterDataDto->getEntityAlias();
        $property = $filterDataDto->getProperty();
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $parameter2Name = $filterDataDto->getParameter2Name();
        $value = $filterDataDto->getValue();
        $value2 = $filterDataDto->getValue2();

        if (ComparisonType::BETWEEN === $comparison) {
            $queryBuilder->andWhere(sprintf('%s.%s BETWEEN :%s and :%s', $alias, $property, $parameterName, $parameter2Name))
                ->setParameter($parameterName, $value)
                ->setParameter($parameter2Name, $value2);
        } else {
            $queryBuilder->andWhere(sprintf('%s.%s %s :%s', $alias, $property, $comparison, $parameterName))
                ->setParameter($parameterName, $value);
        }
    }
}
