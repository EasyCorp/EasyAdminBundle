<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ArrayFilter implements FilterInterface
{
    use FilterTrait;

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ArrayFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle');
    }

    /**
     * @param array<mixed> $choices
     */
    public function setChoices(array $choices): self
    {
        $this->dto->setFormTypeOption('value_type_options.choices', $choices);

        return $this;
    }

    /**
     * @param array<string|TranslatableInterface> $choiceGenerator
     */
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
            $clause = ComparisonType::CONTAINS_ALL === $comparison ? new Andx() : new Orx();
            $comparison = ComparisonType::CONTAINS_ALL === $comparison ? 'LIKE' : $comparison;
            foreach ($value as $key => $item) {
                $itemParameterName = sprintf('%s_%s', $parameterName, $key);
                $clause->add(sprintf('%s.%s %s :%s', $alias, $property, $comparison, $itemParameterName));
                $queryBuilder->setParameter($itemParameterName, $useQuotes ? '%"'.$item.'"%' : '%'.$item.'%');
            }
            if (ComparisonType::NOT_CONTAINS === $comparison) {
                $clause->add(sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($clause);
        }
    }
}
