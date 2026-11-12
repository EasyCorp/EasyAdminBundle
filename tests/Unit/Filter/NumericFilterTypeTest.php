<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\NumericFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Test\TypeTestCase;

class NumericFilterTypeTest extends TypeTestCase
{
    #[DataProvider('submit')]
    public function test(array $options, array $dataToSubmit, array $expectedData, string $expectedError = ''): void
    {
        $form = $this->factory->create(NumericFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        if ($form->isValid()) {
            $this->assertEquals($expectedData, $form->getData());
            $this->assertEmpty($form->getExtraData());
            $this->assertTrue($form->isSynchronized());
            $this->assertInstanceOf(NumericFilterType::class, $form->getConfig()->getType()->getInnerType());
        } else {
            $this->assertSame($expectedError, $form->getTransformationFailure()->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $dataToSubmit
     */
    #[DataProvider('malformedData')]
    public function testMalformedDataMakesTheFilterInvalid(array $options, array $dataToSubmit): void
    {
        $form = $this->factory->create(NumericFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertFalse($form->isSynchronized());
        $this->assertFalse($form->isValid());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function malformedData(): iterable
    {
        yield 'unknown comparison' => [[], ['comparison' => 'bogus', 'value' => '1']];
        yield 'text value' => [[], ['comparison' => ComparisonType::EQ, 'value' => 'abc']];
        yield 'text second value' => [[], ['comparison' => ComparisonType::BETWEEN, 'value' => '1', 'value2' => 'abc']];
        yield 'array second value' => [[], ['comparison' => ComparisonType::BETWEEN, 'value' => '1', 'value2' => ['1']]];
    }

    public static function submit(): iterable
    {
        yield [
            ['value_type' => IntegerType::class],
            ['comparison' => ComparisonType::EQ, 'value' => '23', 'value2' => null],
            ['comparison' => '=', 'value' => 23, 'value2' => null],
        ];
        yield [
            [],
            ['comparison' => ComparisonType::GT, 'value' => '23.23', 'value2' => null],
            ['comparison' => '>', 'value' => 23.23, 'value2' => null],
        ];
        yield [
            ['value_type' => IntegerType::class],
            ['comparison' => ComparisonType::BETWEEN, 'value' => '23', 'value2' => '32'],
            ['comparison' => 'between', 'value' => '23', 'value2' => '32'],
        ];
        yield [
            ['value_type' => IntegerType::class],
            ['comparison' => ComparisonType::BETWEEN, 'value' => '32', 'value2' => '23'],
            ['comparison' => 'between', 'value' => '23', 'value2' => '32'],
        ];
        yield [
            [],
            ['comparison' => ComparisonType::BETWEEN, 'value' => '23.32', 'value2' => null],
            ['comparison' => 'between', 'value' => '23.32', 'value2' => null],
            'Unable to reverse value for property path "ea_numeric_filter": Two values must be provided when "BETWEEN" comparison is selected.',
        ];
        yield [
            [],
            ['comparison' => ComparisonType::EQ, 'value' => null, 'value2' => null],
            ['comparison' => 'IS NULL', 'value' => null, 'value2' => null],
        ];
        yield [
            [],
            ['comparison' => ComparisonType::NEQ, 'value' => null, 'value2' => null],
            ['comparison' => 'IS NOT NULL', 'value' => null, 'value2' => null],
        ];
        yield [
            [],
            ['comparison' => ComparisonType::GT, 'value' => null, 'value2' => null],
            ['comparison' => 'IS NOT NULL', 'value' => null, 'value2' => null],
        ];
    }
}
