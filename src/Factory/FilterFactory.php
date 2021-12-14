<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Types;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterFactory
{
    private $adminContextProvider;
    private $entityFactory;
    private $filterConfigurators;
    private static $doctrineTypeToFilterClass = [
        'json_array' => ArrayFilter::class,
        Types::SIMPLE_ARRAY => ArrayFilter::class,
        Types::ARRAY => ArrayFilter::class,
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
        Types::OBJECT => TextFilter::class,
        Types::TEXT => TextFilter::class,
    ];

    public function __construct(AdminContextProvider $adminContextProvider, EntityFactory $entityFactory, iterable $filterConfigurators)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->entityFactory = $entityFactory;
        $this->filterConfigurators = $filterConfigurators;
    }

    public function create(FilterConfigDto $filterConfig, FieldCollection $fields, EntityDto $entityDto): FilterCollection
    {
        $builtFilters = [];
        $filters = $filterConfig->all();
        /** @var FilterInterface|string $filter */

        foreach ($filters as $key => $filter) {
            if (\is_array($filter)) {
                $filters = array_merge($filters, $this->normalizeEmbeddedFilters($key, $filter));
                unset($filters[$key]);
            }
        }

        foreach ($filters as $property => $filter) {
            if (\is_string($filter)) {
                $guessedFilterClass = $this->guessFilterClass($entityDto, $property);
                /** @var FilterInterface $filter */
                $filter = $guessedFilterClass::new($property);
            }

            $filterDto = $filter->getAsDto();

            $context = $this->adminContextProvider->getContext();
            foreach ($this->filterConfigurators as $configurator) {
                if (!$configurator->supports($filterDto, $fields->get($property), $entityDto, $context)) {
                    continue;
                }

                $configurator->configure($filterDto, $fields->get($property), $entityDto, $context);
            }

            $builtFilters[$property] = $filterDto;
        }

        return FilterCollection::new($builtFilters);
    }

    private function guessFilterClass(EntityDto $entityDto, string $propertyName, array $context = []): string
    {
        if ($entityDto->isAssociation($propertyName)) {
            return EntityFilter::class;
        }

        if ($entityDto->isEmbeddedClassProperty($propertyName)) {
            $properties = explode('.', $propertyName, 2);
            $context['root_entity'] = $context['root_entity'] ?? $entityDto;
            $context['root_property'] = $context['root_property'] ?? $propertyName;
            $embeddedEntity = $this->entityFactory->create($entityDto->getEmbeddedTargetClassName($propertyName));
            $embeddedProperty = $properties[1] ?? null;

            if (!$embeddedProperty) {
                throw new \LogicException(sprintf('Missing embedded property name for the property "%s" in entity class "%s".', $context['root_property'], $context['root_entity']->getFqcn()));
            }

            return $this->guessFilterClass($embeddedEntity, $properties, $context);
        }

        $metadata = $entityDto->getPropertyMetadata($propertyName);

        if ($metadata->isEmpty()) {
            return TextFilter::class;
        }

        return self::$doctrineTypeToFilterClass[$metadata->get('type')] ?? TextFilter::class;
    }

    private function normalizeEmbeddedFilters(string $rootPropertyName, array $embeddedFilters = []): array
    {
        $filters = [];

        foreach ($embeddedFilters as $propertyName => $embeddedFilter) {
            if (\is_array($embeddedFilter)) {
                $filters = array_merge($filters, $this->normalizeEmbeddedFilters("$rootPropertyName.$propertyName", $embeddedFilter));

                continue;
            }

            /** @var FilterInterface $embeddedFilter */
            $embeddedFilter->getAsDto()->setProperty("$rootPropertyName.$propertyName");
            $filters["$rootPropertyName.$propertyName"] = $embeddedFilter;
        }

        return $filters;
    }
}
