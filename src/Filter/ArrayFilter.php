<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ArrayFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ArrayFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle');
    }

    public function setChoices(array $choices): self
    {
        $this->dto->setFormTypeOption('value_type_options.choices', $choices);

        return $this;
    }

    public function setTranslatableChoices(array $choiceGenerator): self
    {
        $this->dto->setFormTypeOption('value_type_options.choices', array_keys($choiceGenerator));
        $this->dto->setFormTypeOption('value_type_options.choice_label', fn ($value) => $choiceGenerator[$value]);

        return $this;
    }

    public function canSelectMultiple(bool $selectMultiple = true): self
    {
        $this->dto->setFormTypeOption('value_type_options.multiple', $selectMultiple);

        return $this;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();
        $property = $filterDataDto->getProperty();
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $value = $filterDataDto->getValue();

        $useQuotes = Types::SIMPLE_ARRAY === $fieldDto->getDoctrineMetadata()->get('type');

        if (null === $value || [] === $value) {
            $queryBuilder->andWhere(sprintf('%s.%s %s', $alias, $property, $comparison));
        } else {
            $orX = new Orx();
            foreach ($value as $key => $item) {
                // TODO: check this code because the loop variable is not used
                $itemParameterName = sprintf('%s_%s', $parameterName, $key);
                $orX->add(sprintf('%s.%s %s :%s', $alias, $property, $comparison, $itemParameterName));
                $queryBuilder->setParameter($itemParameterName, $useQuotes ? '%"'.$item.'"%' : '%'.$item.'%');
            }
            if (ComparisonType::NOT_CONTAINS === $comparison) {
                $orX->add(sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($orX);
        }
    }
}
