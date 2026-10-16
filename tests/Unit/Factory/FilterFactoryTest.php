<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Factory;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Orm\NestedAssociationResolverInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ResolvedPropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ArrayFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;

class FilterFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;

    private AdminContextProviderInterface $adminContextProvider;
    private FilterFactory $filterFactory;

    protected function setUp(): void
    {
        $this->adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $context = $this->createAdminContext();
        $this->adminContextProvider->method('getContext')->willReturn($context);
    }

    public function testCreateWithExplicitFilterInstance(): void
    {
        $this->filterFactory = $this->createFilterFactory();

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter(TextFilter::new('name'));

        $entityDto = $this->createEntityDto(['name' => Types::STRING]);
        $fields = new FieldCollection([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(FilterDto::class, $result->get('name'));
        $this->assertSame('name', $result->get('name')->getProperty());
    }

    /**
     * @dataProvider doctrineTypeToFilterProvider
     */
    public function testCreateGuessesFilterForDoctrineType(string $propertyName, string $doctrineType, string $expectedFilterFqcn): void
    {
        $this->filterFactory = $this->createFilterFactory();

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter($propertyName);

        $entityDto = $this->createEntityDto([$propertyName => $doctrineType]);
        $fields = new FieldCollection([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(1, $result);
        $filter = $result->get($propertyName);
        $this->assertNotNull($filter);
        $this->assertSame($propertyName, $filter->getProperty());
        $this->assertSame($expectedFilterFqcn, $filter->getFqcn());
    }

    public static function doctrineTypeToFilterProvider(): \Generator
    {
        // text filters
        yield 'string type' => ['title', Types::STRING, TextFilter::class];
        yield 'text type' => ['description', Types::TEXT, TextFilter::class];
        yield 'guid type' => ['uuid', Types::GUID, TextFilter::class];
        yield 'json type' => ['metadata', Types::JSON, TextFilter::class];

        // boolean filter
        yield 'boolean type' => ['isActive', Types::BOOLEAN, BooleanFilter::class];

        // dateTime filters
        yield 'datetime mutable type' => ['createdAt', Types::DATETIME_MUTABLE, DateTimeFilter::class];
        yield 'datetime immutable type' => ['publishedAt', Types::DATETIME_IMMUTABLE, DateTimeFilter::class];
        yield 'date mutable type' => ['birthDate', Types::DATE_MUTABLE, DateTimeFilter::class];
        yield 'time mutable type' => ['startTime', Types::TIME_MUTABLE, DateTimeFilter::class];

        // numeric filters
        yield 'integer type' => ['quantity', Types::INTEGER, NumericFilter::class];
        yield 'float type' => ['price', Types::FLOAT, NumericFilter::class];
        yield 'decimal type' => ['amount', Types::DECIMAL, NumericFilter::class];
        yield 'bigint type' => ['largeNumber', Types::BIGINT, NumericFilter::class];
        yield 'smallint type' => ['priority', Types::SMALLINT, NumericFilter::class];

        // array filter
        yield 'simple array type' => ['tags', Types::SIMPLE_ARRAY, ArrayFilter::class];
    }

    public function testCreateGuessesEntityFilterForAssociation(): void
    {
        $this->filterFactory = $this->createFilterFactory();

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter('category');

        $entityDto = $this->createEntityDtoWithAssociation('category', 'App\Entity\Category');
        $fields = new FieldCollection([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(1, $result);
        $this->assertSame(EntityFilter::class, $result->get('category')->getFqcn());
    }

    public function testCreateHandlesMultipleFilters(): void
    {
        $this->filterFactory = $this->createFilterFactory();

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter('name');
        $filterConfig->addFilter('isActive');
        $filterConfig->addFilter('createdAt');

        $entityDto = $this->createEntityDto([
            'name' => Types::STRING,
            'isActive' => Types::BOOLEAN,
            'createdAt' => Types::DATETIME_MUTABLE,
        ]);
        $fields = new FieldCollection([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(3, $result);
        $this->assertNotNull($result->get('name'));
        $this->assertNotNull($result->get('isActive'));
        $this->assertNotNull($result->get('createdAt'));
    }

    public function testCreateAppliesConfigurators(): void
    {
        $configurator = $this->createMock(FilterConfiguratorInterface::class);
        $configurator->method('supports')->willReturn(true);
        $configurator->expects($this->once())->method('configure');

        $this->filterFactory = $this->createFilterFactory([$configurator]);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter(TextFilter::new('name'));

        $entityDto = $this->createEntityDto(['name' => Types::STRING]);
        $fields = new FieldCollection([]);

        $this->filterFactory->create($filterConfig, $fields, $entityDto);
    }

    public function testCreateSkipsNonSupportingConfigurators(): void
    {
        $configurator = $this->createMock(FilterConfiguratorInterface::class);
        $configurator->method('supports')->willReturn(false);
        $configurator->expects($this->never())->method('configure');

        $this->filterFactory = $this->createFilterFactory([$configurator]);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter(TextFilter::new('name'));

        $entityDto = $this->createEntityDto(['name' => Types::STRING]);
        $fields = new FieldCollection([]);

        $this->filterFactory->create($filterConfig, $fields, $entityDto);
    }

    public function testCreateReturnsEmptyCollectionWhenNoFilters(): void
    {
        $this->filterFactory = $this->createFilterFactory();

        $filterConfig = new FilterConfigDto();
        $entityDto = $this->createEntityDto([]);
        $fields = new FieldCollection([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(0, $result);
    }

    public function testCreateGuessesTextFilterForEmbeddedClass(): void
    {
        $this->filterFactory = $this->createFilterFactory();

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter('address');

        $entityDto = $this->createEntityDtoWithEmbedded('address');
        $fields = new FieldCollection([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(1, $result);
        $this->assertSame(TextFilter::class, $result->get('address')->getFqcn());
    }

    /**
     * @dataProvider nestedFieldProvider
     */
    public function testCreateGuessesFilterForNestedField(string $propertyPath, string $resolvedPropertyName, string $doctrineType, string $expectedFilterFqcn): void
    {
        $resolvedEntityDto = $this->createEntityDto([$resolvedPropertyName => $doctrineType]);

        $this->assertGuessedFilterForNestedProperty($propertyPath, $resolvedEntityDto, $resolvedPropertyName, $expectedFilterFqcn);
    }

    public static function nestedFieldProvider(): \Generator
    {
        yield 'string field' => ['author.name', 'name', Types::STRING, TextFilter::class];
        yield 'date field' => ['author.birthDate', 'birthDate', Types::DATE_MUTABLE, DateTimeFilter::class];
        yield 'integer field' => ['author.age', 'age', Types::INTEGER, NumericFilter::class];
        yield 'boolean field of a deeper association' => ['author.publisher.isActive', 'isActive', Types::BOOLEAN, BooleanFilter::class];
        yield 'embedded field' => ['author.address.country', 'address.country', Types::STRING, TextFilter::class];
    }

    public function testCreateGuessesEntityFilterForNestedAssociation(): void
    {
        $resolvedEntityDto = $this->createEntityDtoWithAssociation('publisher', 'App\Entity\Publisher');

        $this->assertGuessedFilterForNestedProperty('author.publisher', $resolvedEntityDto, 'publisher', EntityFilter::class);
    }

    public function testCreateGuessesTextFilterForNestedEmbeddedClass(): void
    {
        $resolvedEntityDto = $this->createEntityDtoWithEmbedded('address');

        $this->assertGuessedFilterForNestedProperty('author.address', $resolvedEntityDto, 'address', TextFilter::class);
    }

    /**
     * @param iterable<FilterConfiguratorInterface> $filterConfigurators
     */
    private function createFilterFactory(iterable $filterConfigurators = [], ?NestedAssociationResolverInterface $associationResolver = null): FilterFactory
    {
        if (null === $associationResolver) {
            // properties of the root entity resolve to that same entity and property name
            $associationResolver = $this->createStub(NestedAssociationResolverInterface::class);
            $associationResolver->method('resolveNestedAssociations')->willReturnCallback(
                static fn ($queryBuilder, EntityDto $entityDto, string $propertyName): ResolvedPropertyDto => new ResolvedPropertyDto($entityDto, null, $propertyName)
            );
        }

        return new FilterFactory($this->adminContextProvider, $filterConfigurators, $associationResolver);
    }

    /**
     * @group legacy
     */
    public function testCreateWithoutAssociationResolverGuessesRootProperties(): void
    {
        $this->expectDeprecation('Since easycorp/easyadmin-bundle 5.7.0: Not passing an instance of "EasyCorp\\Bundle\\EasyAdminBundle\\Contracts\\Orm\\NestedAssociationResolverInterface" as the third argument of "EasyCorp\\Bundle\\EasyAdminBundle\\Factory\\FilterFactory::__construct()" is deprecated and it will be required in EasyAdmin 6.0.');
        $filterFactory = new FilterFactory($this->adminContextProvider, []);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter('isActive');

        $result = $filterFactory->create($filterConfig, new FieldCollection([]), $this->createEntityDto(['isActive' => Types::BOOLEAN]));

        $this->assertSame(BooleanFilter::class, $result->get('isActive')->getFqcn());
    }

    private function assertGuessedFilterForNestedProperty(string $propertyPath, EntityDto $resolvedEntityDto, string $resolvedPropertyName, string $expectedFilterFqcn): void
    {
        $rootEntityDto = $this->createEntityDtoWithAssociation('author', 'App\\Entity\\User');

        $associationResolver = $this->createMock(NestedAssociationResolverInterface::class);
        $associationResolver->expects($this->once())
            ->method('resolveNestedAssociations')
            ->with(null, $rootEntityDto, $propertyPath, true)
            ->willReturn(new ResolvedPropertyDto($resolvedEntityDto, null, $resolvedPropertyName));

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter($propertyPath);

        $result = $this->createFilterFactory([], $associationResolver)->create($filterConfig, new FieldCollection([]), $rootEntityDto);

        $this->assertCount(1, $result);
        $this->assertSame($propertyPath, $result->get($propertyPath)->getProperty());
        $this->assertSame($expectedFilterFqcn, $result->get($propertyPath)->getFqcn());
    }

    private function createAdminContext(): AdminContext
    {
        return AdminContext::forTesting(
            RequestContext::forTesting(new Request()),
            null,
            null,
            I18nContext::forTesting('en', 'ltr')
        );
    }

    /**
     * @param array<string, string> $fieldTypes
     */
    private function createEntityDto(array $fieldTypes): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->embeddedClasses = [];

        $fieldMappings = [];
        foreach ($fieldTypes as $fieldName => $fieldType) {
            // doctrine ORM 2.x uses arrays, Doctrine ORM 3.x uses FieldMapping objects
            $fieldMappings[$fieldName] = class_exists(FieldMapping::class)
                ? new FieldMapping($fieldType, $fieldName, $fieldName)
                : ['fieldName' => $fieldName, 'type' => $fieldType, 'columnName' => $fieldName];
        }
        $metadata->fieldMappings = $fieldMappings;

        $metadata->method('getFieldMapping')->willReturnCallback(static function ($fieldName) use ($fieldMappings) {
            return $fieldMappings[$fieldName] ?? throw new \InvalidArgumentException("Unknown field: $fieldName");
        });

        return new EntityDto('App\Entity\Product', $metadata);
    }

    private function createEntityDtoWithAssociation(string $associationName, string $targetClass): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('hasAssociation')->willReturnCallback(static function ($name) use ($associationName) {
            return $name === $associationName;
        });
        $metadata->method('getAssociationTargetClass')->with($associationName)->willReturn($targetClass);
        $metadata->embeddedClasses = [];
        $metadata->fieldMappings = [];

        return new EntityDto('App\Entity\Product', $metadata);
    }

    private function createEntityDtoWithEmbedded(string $embeddedName): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('hasAssociation')->willReturn(false);
        $metadata->embeddedClasses = [$embeddedName => ['class' => 'App\Entity\Address']];
        $metadata->fieldMappings = [];

        return new EntityDto('App\Entity\Product', $metadata);
    }
}
