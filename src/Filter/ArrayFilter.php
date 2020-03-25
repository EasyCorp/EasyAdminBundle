<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use function Symfony\Component\String\u;

final class ArrayFilter implements FilterInterface
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
        $filter->formType = ArrayFilterType::class;
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

    public function getAsDto(): FilterDto
    {
        // TODO: fix this
        return new FilterDto();
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
        $value = $filterDataDto->getValue();

        // TODO: allow to do this:
        //$property = $metadata['field'];
        //$useQuotes = 'simple_array' !== $metadata['dataType'];
        $useQuotes = true;

        if (null === $value || [] === $value) {
            $queryBuilder->andWhere(sprintf('%s.%s %s', $alias, $property, $comparison));
        } else {
            $orX = new Expr\Orx();
            foreach ($value as $item) {
                $orX->add(sprintf('%s.%s %s :%s', $alias, $property, $comparison, $parameterName));
                $queryBuilder->setParameter($parameterName, $useQuotes ? '%"'.$item.'"%' : '%'.$item.'%');
            }
            if (ComparisonType::NOT_CONTAINS === $comparison) {
                $orX->add(sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($orX);
        }
    }
}
