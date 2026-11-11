<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\Test\TypeTestCase;

class ArrayFilterTypeTest extends TypeTestCase
{
    #[DataProvider('submit')]
    public function testSubmit(array $options, array $dataToSubmit, array $expectedData): void
    {
        $form = $this->factory->create(ArrayFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertSame($dataToSubmit, $form->getViewData());
        $this->assertSame($expectedData, $form->getData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(ArrayFilterType::class, $form->getConfig()->getType()->getInnerType());
    }

    /**
     * @param array<string, mixed>  $valueTypeOptions
     * @param array<string, string> $expectedAttr
     */
    #[DataProvider('valueTypeOptions')]
    public function testValueTypeOptionsKeepTheDefaultsThatAreNotSet(array $valueTypeOptions, bool $expectedMultiple, array $expectedAttr): void
    {
        $form = $this->factory->create(ArrayFilterType::class, null, ['value_type_options' => $valueTypeOptions]);
        $valueConfig = $form->get('value')->getConfig();

        $this->assertSame($expectedMultiple, $valueConfig->getOption('multiple'));
        $this->assertSame($expectedAttr, $valueConfig->getOption('attr'));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, bool, array<string, string>}>
     */
    public static function valueTypeOptions(): iterable
    {
        $choices = ['a' => 'a', 'b' => 'b'];
        $autocomplete = ['data-ea-widget' => 'ea-autocomplete'];
        $autocompleteWithNewItems = $autocomplete + ['data-ea-autocomplete-allow-item-create' => 'true'];

        yield 'no options' => [[], true, $autocompleteWithNewItems];
        yield 'choices' => [['choices' => $choices], true, $autocomplete];
        yield 'empty choices' => [['choices' => []], true, $autocompleteWithNewItems];
        yield 'single selection' => [['choices' => $choices, 'multiple' => false], false, $autocomplete];
        yield 'custom attributes' => [['choices' => $choices, 'attr' => ['class' => 'custom']], true, ['class' => 'custom']];
        yield 'no attributes' => [['attr' => []], true, []];
        yield 'expanded' => [['choices' => $choices, 'expanded' => true], true, []];
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $dataToSubmit
     */
    #[DataProvider('malformedData')]
    public function testMalformedDataMakesTheFilterInvalid(array $options, array $dataToSubmit): void
    {
        $form = $this->factory->create(ArrayFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertFalse($form->isSynchronized());
        $this->assertFalse($form->isValid());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, array<string, mixed>}>
     */
    public static function malformedData(): iterable
    {
        yield 'unknown comparison' => [[], ['comparison' => 'bogus']];
        yield 'scalar value' => [[], ['comparison' => ComparisonType::CONTAINS, 'value' => 'a']];
        yield 'scalar value with choices' => [['value_type_options' => ['choices' => ['a' => 'a', 'b' => 'b']]], ['comparison' => ComparisonType::CONTAINS, 'value' => 'a']];
    }

    public static function submit(): iterable
    {
        yield [
            [],
            ['comparison' => ComparisonType::CONTAINS, 'value' => ['bar']],
            ['comparison' => 'like', 'value' => ['bar']],
        ];
        yield [
            [],
            ['comparison' => ComparisonType::CONTAINS_ALL, 'value' => ['bar', 'baz']],
            ['comparison' => 'like_all', 'value' => ['bar', 'baz']],
        ];
        yield [
            [],
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => ['foo', 'bar']],
            ['comparison' => 'not like', 'value' => ['foo', 'bar']],
        ];
        yield [
            [],
            ['comparison' => ComparisonType::CONTAINS, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => []],
        ];
        yield [
            [],
            ['comparison' => ComparisonType::CONTAINS_ALL, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => []],
        ];
        yield [
            [
                'value_type_options' => [
                    'multiple' => false,
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            ['comparison' => ComparisonType::CONTAINS, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
        ];
        yield [
            [
                'value_type_options' => [
                    'multiple' => false,
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            ['comparison' => ComparisonType::CONTAINS_ALL, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
        ];
        yield [
            [
                'value_type_options' => [
                    'multiple' => false,
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            ['comparison' => ComparisonType::CONTAINS, 'value' => 'b'],
            ['comparison' => 'like', 'value' => ['b']],
        ];
        yield [
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => ['a', 'c']],
            ['comparison' => 'not like', 'value' => ['a', 'c']],
        ];
    }
}
