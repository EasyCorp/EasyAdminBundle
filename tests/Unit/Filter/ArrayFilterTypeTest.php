<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Test\TypeTestCase;

class ArrayFilterTypeTest extends TypeTestCase
{
    /**
     * @dataProvider submit
     */
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
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            ['comparison' => ComparisonType::CONTAINS, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
        ];
        yield [
            [
                'value_type_options' => [
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            ['comparison' => ComparisonType::CONTAINS_ALL, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
        ];
        yield [
            [
                'value_type_options' => [
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
