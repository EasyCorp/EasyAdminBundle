<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Test\TypeTestCase;

class ChoiceFilterTypeTest extends TypeTestCase
{
    #[DataProvider('submit')]
    public function test(array $options, array $dataToSubmit, array $expectedData): void
    {
        $form = $this->factory->create(ChoiceFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertSame($dataToSubmit, $form->getViewData());
        $this->assertSame($expectedData, $form->getData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(ChoiceFilterType::class, $form->getConfig()->getType()->getInnerType());
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $dataToSubmit
     */
    #[DataProvider('malformedData')]
    public function testMalformedDataMakesTheFilterInvalid(array $options, array $dataToSubmit): void
    {
        $form = $this->factory->create(ChoiceFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertFalse($form->isSynchronized());
        $this->assertFalse($form->isValid());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function malformedData(): iterable
    {
        yield 'unknown comparison' => [['value_type_options' => ['choices' => ['a' => 'a', 'b' => 'b']]], ['comparison' => 'bogus', 'value' => 'a']];
        yield 'unknown value' => [['value_type_options' => ['choices' => ['a' => 'a', 'b' => 'b']]], ['comparison' => ComparisonType::EQ, 'value' => 'bogus']];
        yield 'array value' => [['value_type_options' => ['choices' => ['a' => 'a', 'b' => 'b']]], ['comparison' => ComparisonType::EQ, 'value' => ['a']]];
    }

    public static function submit(): iterable
    {
        yield [
            [
                'value_type_options' => [
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
        ];
        yield [
            [
                'value_type_options' => [
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            ['comparison' => ComparisonType::NEQ, 'value' => null],
            ['comparison' => 'IS NOT NULL', 'value' => null],
        ];
        yield [
            [
                'value_type_options' => [
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => 'a'],
            ['comparison' => '=', 'value' => 'a'],
        ];
        yield [
            [
                'value_type_options' => [
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            ['comparison' => ComparisonType::NEQ, 'value' => 'b'],
            ['comparison' => '!=', 'value' => 'b'],
        ];
        yield [
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => []],
        ];
        yield [
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            ['comparison' => ComparisonType::NEQ, 'value' => []],
            ['comparison' => 'IS NOT NULL', 'value' => []],
        ];
        yield [
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            ['comparison' => ComparisonType::EQ, 'value' => ['a', 'b']],
            ['comparison' => 'IN', 'value' => ['a', 'b']],
        ];
        yield [
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            ['comparison' => ComparisonType::NEQ, 'value' => ['b', 'c']],
            ['comparison' => 'NOT IN', 'value' => ['b', 'c']],
        ];
    }
}
