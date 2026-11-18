<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class DateTimeFilter implements FilterInterface
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
            ->setFormType(DateTimeFilterType::class)
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

        if (null === $value) {
            $queryBuilder->andWhere(sprintf('%s.%s %s', $alias, $property, $comparison));
        } elseif (ComparisonType::BETWEEN === $comparison) {
            $queryBuilder->andWhere(sprintf('%s.%s BETWEEN :%s and :%s', $alias, $property, $parameterName, $parameter2Name))
                ->setParameter($parameterName, $value, $this->getUtcParameterType($filterDataDto, $entityDto, $value))
                ->setParameter($parameter2Name, $value2, $this->getUtcParameterType($filterDataDto, $entityDto, $value2));
        } else {
            $queryBuilder->andWhere(sprintf('%s.%s %s :%s', $alias, $property, $comparison, $parameterName))
                ->setParameter($parameterName, $value, $this->getUtcParameterType($filterDataDto, $entityDto, $value));
        }
    }

    /**
     * Doctrine infers the 'datetime' type for date values, which formats them in their own
     * timezone. Columns that store UTC values need their own type to convert the value to
     * UTC before comparing it. For any other column it returns null to let Doctrine infer it.
     */
    private function getUtcParameterType(FilterDataDto $filterDataDto, EntityDto $entityDto, mixed $value): ?string
    {
        if (!$value instanceof \DateTimeInterface) {
            return null;
        }

        // for nested properties (e.g. 'author.createdAt'), the column type is defined
        // in the metadata of the entity that holds the property instead of the root entity
        $classMetadata = ($filterDataDto->getEntityDto() ?? $entityDto)->getClassMetadata();
        $fieldMapping = $classMetadata->fieldMappings[$filterDataDto->getProperty()] ?? null;
        if (null === $fieldMapping) {
            return null;
        }

        // @phpstan-ignore-next-line (backward compatibility with Doctrine ORM 2.x)
        $fieldType = \is_array($fieldMapping) ? ($fieldMapping['type'] ?? null) : $fieldMapping->type;
        // don't use Types::DATETIME_UTC_* because they were added in Doctrine DBAL 4.5
        if (!\in_array($fieldType, ['datetime_utc', 'datetime_utc_immutable'], true)) {
            return null;
        }

        // each DBAL type only accepts its own PHP class, so the type depends on the value
        // (which comes from the form) and not on the column
        return $value instanceof \DateTimeImmutable ? 'datetime_utc_immutable' : 'datetime_utc';
    }
}
