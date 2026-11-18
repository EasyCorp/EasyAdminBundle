<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ResolvedPropertyDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The UTC types are string literals (and their metadata is stubbed) because
 * their constants need Doctrine DBAL 4.5 and the test suite also runs with older versions.
 */
class DateTimeFilterTest extends TestCase
{
    #[DataProvider('utcColumnProvider')]
    public function testApplyBindsUtcTypeForUtcColumns(string $doctrineType, \DateTimeInterface $value, string $expectedParameterType): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $filter = DateTimeFilter::new('createdAt');
        $filterDataDto = FilterDataDto::new(0, $filter->getAsDto(), 'entity', [
            'comparison' => ComparisonType::GT,
            'value' => $value,
        ]);

        $filter->apply($queryBuilder, $filterDataDto, null, $this->createEntityDto(['createdAt' => $doctrineType]));

        $this->assertSame('entity.createdAt > :createdAt_0', (string) $queryBuilder->getDQLPart('where'));
        $parameter = $queryBuilder->getParameter('createdAt_0');
        $this->assertSame($value, $parameter->getValue());
        $this->assertTrue($parameter->typeWasSpecified());
        $this->assertSame($expectedParameterType, $parameter->getType());
    }

    public static function utcColumnProvider(): \Generator
    {
        yield 'mutable column and mutable value' => ['datetime_utc', new \DateTime('2026-01-15 10:00'), 'datetime_utc'];
        yield 'immutable column and immutable value' => ['datetime_utc_immutable', new \DateTimeImmutable('2026-01-15 10:00'), 'datetime_utc_immutable'];
        // each DBAL type only accepts its own PHP class, and the class of the
        // value depends on the form options, which the user can change
        yield 'mutable column and immutable value' => ['datetime_utc', new \DateTimeImmutable('2026-01-15 10:00'), 'datetime_utc_immutable'];
        yield 'immutable column and mutable value' => ['datetime_utc_immutable', new \DateTime('2026-01-15 10:00'), 'datetime_utc'];
    }

    public function testApplyBindsUtcTypeForBothValuesOfBetweenComparison(): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $filter = DateTimeFilter::new('createdAt');
        $filterDataDto = FilterDataDto::new(0, $filter->getAsDto(), 'entity', [
            'comparison' => ComparisonType::BETWEEN,
            'value' => new \DateTimeImmutable('2026-01-15 10:00'),
            'value2' => new \DateTimeImmutable('2026-01-20 10:00'),
        ]);

        $filter->apply($queryBuilder, $filterDataDto, null, $this->createEntityDto(['createdAt' => 'datetime_utc_immutable']));

        $this->assertSame('entity.createdAt BETWEEN :createdAt_0 and :createdAt_1', (string) $queryBuilder->getDQLPart('where'));
        $this->assertSame('datetime_utc_immutable', $queryBuilder->getParameter('createdAt_0')->getType());
        $this->assertSame('datetime_utc_immutable', $queryBuilder->getParameter('createdAt_1')->getType());
    }

    public function testApplyBindsUtcTypeForNestedProperties(): void
    {
        $queryBuilder = $this->createQueryBuilder();
        // the UTC column is only defined in the resolved entity, not in the root entity
        $authorEntityDto = $this->createEntityDto(['createdAt' => 'datetime_utc_immutable']);
        $rootEntityDto = $this->createEntityDto([]);

        $filter = DateTimeFilter::new('author.createdAt');
        $filterDataDto = FilterDataDto::new(0, $filter->getAsDto(), 'entity', [
            'comparison' => ComparisonType::EQ,
            'value' => new \DateTimeImmutable('2026-01-15 10:00'),
        ], new ResolvedPropertyDto($authorEntityDto, 'author', 'createdAt'));

        $filter->apply($queryBuilder, $filterDataDto, null, $rootEntityDto);

        $this->assertSame('datetime_utc_immutable', $queryBuilder->getParameter('author_createdAt_0')->getType());
    }

    #[DataProvider('untypedParameterProvider')]
    public function testApplyDoesNotBindTypeForOtherColumnsAndValues(array $fieldTypes, mixed $value): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $filter = DateTimeFilter::new('createdAt');
        $filterDataDto = FilterDataDto::new(0, $filter->getAsDto(), 'entity', [
            'comparison' => ComparisonType::BETWEEN,
            'value' => $value,
            'value2' => $value,
        ]);

        $filter->apply($queryBuilder, $filterDataDto, null, $this->createEntityDto($fieldTypes));

        $this->assertFalse($queryBuilder->getParameter('createdAt_0')->typeWasSpecified());
        $this->assertFalse($queryBuilder->getParameter('createdAt_1')->typeWasSpecified());
    }

    public static function untypedParameterProvider(): \Generator
    {
        yield 'datetime column' => [['createdAt' => Types::DATETIME_MUTABLE], new \DateTime('2026-01-15 10:00')];
        yield 'immutable datetime column' => [['createdAt' => Types::DATETIME_IMMUTABLE], new \DateTimeImmutable('2026-01-15 10:00')];
        yield 'unmapped property' => [[], new \DateTime('2026-01-15 10:00')];
        // the UTC types of DBAL reject values that aren't date objects
        yield 'UTC column and string value' => [['createdAt' => 'datetime_utc'], '2026-01-15 10:00:00'];
    }

    private function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = new QueryBuilder($this->createStub(EntityManagerInterface::class));
        $queryBuilder->select('entity')->from(\stdClass::class, 'entity');

        return $queryBuilder;
    }

    /**
     * @param array<string, string> $fieldTypes
     */
    private function createEntityDto(array $fieldTypes): EntityDto
    {
        $classMetadata = $this->createStub(ClassMetadata::class);
        $classMetadata->fieldMappings = [];
        foreach ($fieldTypes as $fieldName => $fieldType) {
            // Doctrine ORM 2.x uses arrays, Doctrine ORM 3.x uses FieldMapping objects
            $classMetadata->fieldMappings[$fieldName] = class_exists(FieldMapping::class)
                ? new FieldMapping($fieldType, $fieldName, $fieldName)
                : ['fieldName' => $fieldName, 'type' => $fieldType, 'columnName' => $fieldName];
        }

        return new EntityDto(\stdClass::class, $classMetadata);
    }
}
