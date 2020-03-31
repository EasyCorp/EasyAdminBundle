<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use function Symfony\Component\String\u;

final class EntityFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle');
    }

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
