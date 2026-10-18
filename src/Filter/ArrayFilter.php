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
        $this->dto->setFormTypeOption('value_type_options.choice_label', static fn ($value) => $choiceGenerator[$value]);

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

        // the Doctrine type is read from the entity metadata instead of $fieldDto because the DTO
        // can be null (e.g. when the filtered property is not configured as a field of the current page)
        $propertyType = $entityDto->getClassMetadata()->getTypeOfField($property);
        $propertyExpression = match ($propertyType) {
            // simple_array values are stored as 'a,b,c'; wrapping them in commas makes the filter match whole items
            Types::SIMPLE_ARRAY => sprintf('CONCAT(\',\', %s.%s, \',\')', $alias, $property),
            // PostgreSQL doesn't support LIKE on JSON columns; concatenating an empty string turns them into text
            Types::JSON => sprintf('CONCAT(%s.%s, \'\')', $alias, $property),
            default => sprintf('%s.%s', $alias, $property),
        };
        $itemPattern = Types::SIMPLE_ARRAY === $propertyType ? '%%,%s,%%' : '%%%s%%';

        if (null === $value || [] === $value) {
            $queryBuilder->andWhere(sprintf('%s.%s %s', $alias, $property, $comparison));
        } else {
            $clause = ComparisonType::CONTAINS_ALL === $comparison ? new Andx() : new Orx();
            $comparison = ComparisonType::CONTAINS_ALL === $comparison ? 'LIKE' : $comparison;
            foreach ($value as $key => $item) {
                $itemParameterName = sprintf('%s_%s', $parameterName, $key);
                $clause->add(sprintf('%s %s :%s', $propertyExpression, $comparison, $itemParameterName));
                $queryBuilder->setParameter($itemParameterName, sprintf($itemPattern, $item));
            }
            if (ComparisonType::NOT_CONTAINS === $comparison) {
                $clause->add(sprintf('%s.%s IS NULL', $alias, $property));
            }
            $queryBuilder->andWhere($clause);
        }
    }
}
