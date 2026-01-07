<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Factory;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\I18nContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\RequestContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterConfigDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class FilterFactoryTest extends TestCase
{
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
        $this->filterFactory = new FilterFactory($this->adminContextProvider, []);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter(TextFilter::new('name'));

        $entityDto = $this->createEntityDto(['name' => Types::STRING]);
        $fields = FieldCollection::new([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(FilterDto::class, $result->get('name'));
        $this->assertSame('name', $result->get('name')->getProperty());
    }

    /**
     * @dataProvider doctrineTypeToFilterProvider
     */
    public function testCreateGuessesFilterForDoctrineType(string $propertyName, string $doctrineType): void
    {
        $this->filterFactory = new FilterFactory($this->adminContextProvider, []);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter($propertyName);

        $entityDto = $this->createEntityDto([$propertyName => $doctrineType]);
        $fields = FieldCollection::new([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(1, $result);
        $filter = $result->get($propertyName);
        $this->assertNotNull($filter);
        $this->assertSame($propertyName, $filter->getProperty());
    }

    public static function doctrineTypeToFilterProvider(): \Generator
    {
        // Text filters
        yield 'string type' => ['title', Types::STRING];
        yield 'text type' => ['description', Types::TEXT];
        yield 'guid type' => ['uuid', Types::GUID];
        yield 'json type' => ['metadata', Types::JSON];

        // Boolean filter
        yield 'boolean type' => ['isActive', Types::BOOLEAN];

        // DateTime filters
        yield 'datetime mutable type' => ['createdAt', Types::DATETIME_MUTABLE];
        yield 'datetime immutable type' => ['publishedAt', Types::DATETIME_IMMUTABLE];
        yield 'date mutable type' => ['birthDate', Types::DATE_MUTABLE];
        yield 'time mutable type' => ['startTime', Types::TIME_MUTABLE];

        // Numeric filters
        yield 'integer type' => ['quantity', Types::INTEGER];
        yield 'float type' => ['price', Types::FLOAT];
        yield 'decimal type' => ['amount', Types::DECIMAL];
        yield 'bigint type' => ['largeNumber', Types::BIGINT];
        yield 'smallint type' => ['priority', Types::SMALLINT];

        // Array filter
        yield 'simple array type' => ['tags', Types::SIMPLE_ARRAY];
    }

    public function testCreateGuessesEntityFilterForAssociation(): void
    {
        $this->filterFactory = new FilterFactory($this->adminContextProvider, []);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter('category');

        $entityDto = $this->createEntityDtoWithAssociation('category', 'App\Entity\Category');
        $fields = FieldCollection::new([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(1, $result);
        $this->assertNotNull($result->get('category'));
    }

    public function testCreateHandlesMultipleFilters(): void
    {
        $this->filterFactory = new FilterFactory($this->adminContextProvider, []);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter('name');
        $filterConfig->addFilter('isActive');
        $filterConfig->addFilter('createdAt');

        $entityDto = $this->createEntityDto([
            'name' => Types::STRING,
            'isActive' => Types::BOOLEAN,
            'createdAt' => Types::DATETIME_MUTABLE,
        ]);
        $fields = FieldCollection::new([]);

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

        $this->filterFactory = new FilterFactory($this->adminContextProvider, [$configurator]);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter(TextFilter::new('name'));

        $entityDto = $this->createEntityDto(['name' => Types::STRING]);
        $fields = FieldCollection::new([]);

        $this->filterFactory->create($filterConfig, $fields, $entityDto);
    }

    public function testCreateSkipsNonSupportingConfigurators(): void
    {
        $configurator = $this->createMock(FilterConfiguratorInterface::class);
        $configurator->method('supports')->willReturn(false);
        $configurator->expects($this->never())->method('configure');

        $this->filterFactory = new FilterFactory($this->adminContextProvider, [$configurator]);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter(TextFilter::new('name'));

        $entityDto = $this->createEntityDto(['name' => Types::STRING]);
        $fields = FieldCollection::new([]);

        $this->filterFactory->create($filterConfig, $fields, $entityDto);
    }

    public function testCreateReturnsEmptyCollectionWhenNoFilters(): void
    {
        $this->filterFactory = new FilterFactory($this->adminContextProvider, []);

        $filterConfig = new FilterConfigDto();
        $entityDto = $this->createEntityDto([]);
        $fields = FieldCollection::new([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(0, $result);
    }

    public function testCreateGuessesTextFilterForEmbeddedClass(): void
    {
        $this->filterFactory = new FilterFactory($this->adminContextProvider, []);

        $filterConfig = new FilterConfigDto();
        $filterConfig->addFilter('address');

        $entityDto = $this->createEntityDtoWithEmbedded('address');
        $fields = FieldCollection::new([]);

        $result = $this->filterFactory->create($filterConfig, $fields, $entityDto);

        $this->assertCount(1, $result);
        $this->assertNotNull($result->get('address'));
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
            // Doctrine ORM 2.x uses arrays, Doctrine ORM 3.x uses FieldMapping objects
            $fieldMappings[$fieldName] = class_exists(FieldMapping::class)
                ? new FieldMapping($fieldName, $fieldType, $fieldName)
                : ['fieldName' => $fieldName, 'type' => $fieldType, 'columnName' => $fieldName];
        }
        $metadata->fieldMappings = $fieldMappings;

        $metadata->method('getFieldMapping')->willReturnCallback(function ($fieldName) use ($fieldMappings) {
            return $fieldMappings[$fieldName] ?? throw new \InvalidArgumentException("Unknown field: $fieldName");
        });

        return new EntityDto('App\Entity\Product', $metadata);
    }

    private function createEntityDtoWithAssociation(string $associationName, string $targetClass): EntityDto
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getSingleIdentifierFieldName')->willReturn('id');
        $metadata->method('hasAssociation')->willReturnCallback(function ($name) use ($associationName) {
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
