<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterFactory
{
    /**
     * @var array<string, class-string<FilterInterface>>
     */
    private static array $doctrineTypeToFilterClass = [
        'json_array' => ArrayFilter::class,
        Types::SIMPLE_ARRAY => ArrayFilter::class,
        'array' => ArrayFilter::class, // don't use Types::ARRAY because it was removed in Doctrine ORM 3.0
        Types::JSON => TextFilter::class,
        Types::BOOLEAN => BooleanFilter::class,
        Types::DATE_MUTABLE => DateTimeFilter::class,
        Types::DATE_IMMUTABLE => DateTimeFilter::class,
        Types::TIME_MUTABLE => DateTimeFilter::class,
        Types::TIME_IMMUTABLE => DateTimeFilter::class,
        Types::DATETIME_MUTABLE => DateTimeFilter::class,
        Types::DATETIMETZ_MUTABLE => DateTimeFilter::class,
        Types::DATETIME_IMMUTABLE => DateTimeFilter::class,
        Types::DATETIMETZ_IMMUTABLE => DateTimeFilter::class,
        Types::DATEINTERVAL => ComparisonFilter::class,
        Types::DECIMAL => NumericFilter::class,
        Types::FLOAT => NumericFilter::class,
        Types::BIGINT => NumericFilter::class,
        Types::INTEGER => NumericFilter::class,
        Types::SMALLINT => NumericFilter::class,
        Types::GUID => TextFilter::class,
        Types::STRING => TextFilter::class,
        Types::BLOB => TextFilter::class,
        'object' => TextFilter::class, // don't use Types::OBJECT because it was removed in Doctrine ORM 3.0
        Types::TEXT => TextFilter::class,
    ];

    /**
     * @param iterable<FilterConfiguratorInterface> $filterConfigurators
     */
    public function __construct(
        private readonly AdminContextProviderInterface $adminContextProvider,
        private readonly iterable $filterConfigurators,
    ) {
    }

    public function create(FilterConfigDto $filterConfig, FieldCollection $fields, EntityDto $entityDto): FilterCollection
    {
        $builtFilters = [];
        /** @var FilterInterface|string $filter */
        foreach ($filterConfig->all() as $property => $filter) {
            if (\is_string($filter)) {
                $guessedFilterClass = $this->guessFilterClass($entityDto, $property);
                /** @var FilterInterface $filter */
                $filter = $guessedFilterClass::new($property);
            }

            $filterDto = $filter->getAsDto();

            $context = $this->adminContextProvider->getContext();
            foreach ($this->filterConfigurators as $configurator) {
                // @phpstan-ignore-next-line argument.type
                if (!$configurator->supports($filterDto, $fields->getByProperty($property), $entityDto, $context)) {
                    continue;
                }

                // @phpstan-ignore-next-line argument.type
                $configurator->configure($filterDto, $fields->getByProperty($property), $entityDto, $context);
            }

            $builtFilters[$property] = $filterDto;
        }

        return FilterCollection::new($builtFilters);
    }

    private function guessFilterClass(EntityDto $entityDto, string $propertyName): string
    {
        if ($entityDto->getClassMetadata()->hasAssociation($propertyName)) {
            return EntityFilter::class;
        }

        if (isset($entityDto->getClassMetadata()->embeddedClasses[$propertyName])) {
            return TextFilter::class;
        }

        // In Doctrine ORM 3.x, FieldMapping implements \ArrayAccess; in 4.x it's an object with properties
        $fieldMapping = $entityDto->getClassMetadata()->getFieldMapping($propertyName);
        // In Doctrine ORM 2.x, getFieldMapping() returns an array
        /** @phpstan-ignore-next-line function.impossibleType */
        if (\is_array($fieldMapping)) {
            /** @phpstan-ignore-next-line cast.useless */
            $fieldMapping = (object) $fieldMapping;
        }
        /** @phpstan-ignore-next-line function.alreadyNarrowedType */
        $fieldType = property_exists($fieldMapping, 'type') ? $fieldMapping->type : $fieldMapping['type'];

        return self::$doctrineTypeToFilterClass[$fieldType] ?? TextFilter::class;
    }
}
