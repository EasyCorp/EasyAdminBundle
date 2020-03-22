<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\FormInterface;
use function Symfony\Component\String\u;

final class EntityFilter
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
        $filter->formType = EntityFilterType::class;
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
        $value = $filterDataDto->getValue();

        // TODO: allow to do this:
        $property = substr($parameterName, -2); // $metadata['field'];
        $multiple = false; //$form->get('value')->getConfig()->getOption('multiple');

        if ('association' === $metadata['dataType'] && $metadata['associationType'] & ClassMetadata::TO_MANY) {
            $assocAlias = static::createAlias($property);
            $queryBuilder->leftJoin(sprintf('%s.%s', $alias, $property), $assocAlias);

            if (0 === \count($value)) {
                $queryBuilder->andWhere(sprintf('%s %s', $assocAlias, $comparison));
            } else {
                $orX = new Expr\Orx();
                $orX->add(sprintf('%s %s (:%s)', $assocAlias, $comparison, $parameterName));
                if ('NOT IN' === $comparison) {
                    $orX->add(sprintf('%s IS NULL', $assocAlias));
                }
                $queryBuilder->andWhere($orX)
                    ->setParameter($parameterName, $value);
            }
        } elseif (null === $value || ($multiple && 0 === \count($value))) {
            $queryBuilder->andWhere(sprintf('%s.%s %s', $alias, $property, $comparison));
        } else {
            $orX = new Expr\Orx();
            $orX->add(sprintf('%s.%s %s (:%s)', $alias, $property, $comparison, $parameterName));
            if (ComparisonType::NEQ === $comparison) {
                $orX->add(sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($orX)
                ->setParameter($parameterName, $value);
        }
    }
}
