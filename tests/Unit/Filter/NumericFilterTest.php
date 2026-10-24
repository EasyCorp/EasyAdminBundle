<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NumericFilterTest extends TestCase
{
    #[DataProvider('provideNullComparisons')]
    public function testApplyNullComparisonWithMoneyFieldStoredAsCents(string $comparison): void
    {
        // when the value of a numeric filter is left empty, NumericFilterType
        // transforms the comparison into 'IS NULL' / 'IS NOT NULL' and keeps the value as null;
        // for money fields stored as cents, the value must not be multiplied by the divisor
        // (null * divisor = 0), because that would generate an invalid DQL query
        // such as 'entity.price IS NULL :price_0'
        $queryBuilder = $this->createQueryBuilder();
        $filterDataDto = $this->createFilterDataDto($comparison, null);
        $fieldDto = MoneyField::new('price')->setCurrency('EUR')->getAsDto();

        NumericFilter::new('price')->apply($queryBuilder, $filterDataDto, $fieldDto, $this->createEntityDto());

        $this->assertSame(sprintf('entity.price %s', $comparison), (string) $queryBuilder->getDQLPart('where'));
        $this->assertCount(0, $queryBuilder->getParameters());
    }

    public static function provideNullComparisons(): iterable
    {
        yield ['IS NULL'];
        yield ['IS NOT NULL'];
    }

    public function testApplyWithMoneyFieldStoredAsCentsMultipliesValueByDivisor(): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $filterDataDto = $this->createFilterDataDto('=', 15.5);
        $fieldDto = MoneyField::new('price')->setCurrency('EUR')->getAsDto();

        NumericFilter::new('price')->apply($queryBuilder, $filterDataDto, $fieldDto, $this->createEntityDto());

        $parameters = $queryBuilder->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame(1550.0, $parameters->first()->getValue());
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->createMock(EntityManagerInterface::class));
    }

    private function createFilterDataDto(string $comparison, mixed $value): FilterDataDto
    {
        $filterDto = NumericFilter::new('price')->getAsDto();

        return FilterDataDto::new(0, $filterDto, 'entity', [
            'comparison' => $comparison,
            'value' => $value,
        ]);
    }

    private function createEntityDto(): EntityDto
    {
        return new EntityDto(\stdClass::class, new ClassMetadata(\stdClass::class));
    }
}
