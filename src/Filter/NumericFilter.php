<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use \Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\MoneyConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\NumericFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class NumericFilter implements FilterInterface
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
            ->setFormType(NumericFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle');
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();
        $property = $filterDataDto->getProperty();
        $comparison = $filterDataDto->getComparison();
        $parameterName = $filterDataDto->getParameterName();
        $parameter2Name = $filterDataDto->getParameter2Name();
        $value = $filterDataDto->getValue();
        $value2 = $filterDataDto->getValue2();

        $storedAsCents = null !== $fieldDto && true === $fieldDto->getCustomOption(MoneyField::OPTION_STORED_AS_CENTS);
        $divisor = $storedAsCents ? ($fieldDto->getFormTypeOption('divisor') ?? MoneyConfigurator::DEFAULT_DIVISOR) : 1;

        if (ComparisonType::BETWEEN === $comparison) {
            if ($storedAsCents) {
                if (is_numeric($value)) {
                    $value *= $divisor;
                }
                if (is_numeric($value2)) {
                    $value2 *= $divisor;
                }
            }
            $queryBuilder
                ->andWhere(sprintf('%s.%s BETWEEN :%s and :%s', $alias, $property, $parameterName, $parameter2Name))
                ->setParameter($parameterName, $value)
                ->setParameter($parameter2Name, $value2);
        } elseif (ComparisonType::IN === $comparison) {
            // allow semicolon-separated or array values for 'IN' comparator (supports integers or decimals)
            $values = is_iterable($value)
                ? $value
                : array_filter(array_map('trim', explode(';', (string) $value)), static fn (string $v): bool => '' !== $v);
            if ($storedAsCents) {
                $values = array_map(static fn ($v) => is_numeric($v) ? $v * $divisor : $v, $values);
            }
            $queryBuilder
                ->andWhere(sprintf('%s.%s IN (:%s)', $alias, $property, $parameterName))
                ->setParameter($parameterName, $values, ArrayParameterType::STRING);
        } else {
            if ($storedAsCents && is_numeric($value)) {
                $value *= $divisor;
            }
            $queryBuilder
                ->andWhere(sprintf('%s.%s %s :%s', $alias, $property, $comparison, $parameterName))
                ->setParameter($parameterName, $value);
        }
    }
}
