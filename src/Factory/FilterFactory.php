<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Factory;

use Doctrine\DBAL\Types\Type;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ComparisonFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Guesser\FilterTypeGuesser;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;

/**
 * @author Yonel Ceruto <yonelceruto@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class FilterFactory
{
    private $adminContextProvider;
    private $filterConfigurators;
    private static $doctrineTypeToFilterClass = [
        Type::JSON_ARRAY => ArrayFilter::class,
        Type::SIMPLE_ARRAY => ArrayFilter::class,
        Type::TARRAY => ArrayFilter::class,
        Type::JSON => TextFilter::class,
        Type::BOOLEAN => BooleanFilter::class,
        Type::DATE => DateTimeFilter::class,
        Type::DATE_IMMUTABLE => DateTimeFilter::class,
        Type::TIME => DateTimeFilter::class,
        Type::TIME_IMMUTABLE => DateTimeFilter::class,
        Type::DATETIME => DateTimeFilter::class,
        Type::DATETIMETZ => DateTimeFilter::class,
        Type::DATETIME_IMMUTABLE => DateTimeFilter::class,
        Type::DATETIMETZ_IMMUTABLE => DateTimeFilter::class,
        Type::DATEINTERVAL => ComparisonFilter::class,
        Type::DECIMAL => NumericFilter::class,
        Type::FLOAT => NumericFilter::class,
        Type::FLOAT => NumericFilter::class,
         Type::BIGINT => NumericFilter::class,
         Type::INTEGER => NumericFilter::class,
         Type::SMALLINT => NumericFilter::class,
         Type::GUID => TextFilter::class,
         Type::STRING => TextFilter::class,
         Type::BLOB => TextFilter::class,
         Type::OBJECT => TextFilter::class,
         Type::TEXT => TextFilter::class,
    ];

    public function __construct(AdminContextProvider $adminContextProvider, iterable $filterConfigurators)
    {
        $this->adminContextProvider = $adminContextProvider;
        $this->filterConfigurators = $filterConfigurators;
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
                if (!$configurator->supports($filterDto, $entityDto)) {
                    continue;
                }

                $configurator->configure($filterDto, $entityDto, $context);
            }

            $builtFilters[$property] = $filterDto;
        }
dd($builtFilters);
        return FilterCollection::new($builtFilters);
    }

    private function guessFilterClass(EntityDto $entityDto, string $propertyName): string
    {
        $metadata = $entityDto->getPropertyMetadata($propertyName);

        if (empty($metadata)) {
            return TextFilter::class;
        }

        return self::$doctrineTypeToFilterClass[$metadata['type']] ?? TextFilter::class;
    }
}
