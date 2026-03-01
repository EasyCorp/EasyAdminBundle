<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Test\TypeTestCase;

class ComparisonFilterTypeTest extends TypeTestCase
{
    /**
     * @dataProvider submit
     */
    public function test(array $options, array $dataToSubmit, array $expectedData): void
    {
        $form = $this->factory->create(ComparisonFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        $this->assertEquals($expectedData, $form->getViewData());
        $this->assertEquals($expectedData, $form->getData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(ComparisonFilterType::class, $form->getConfig()->getType()->getInnerType());
    }

    public static function submit(): iterable
    {
        yield [
            ['value_type' => IntegerType::class],
            ['comparison' => ComparisonType::LT, 'value' => '23'],
            ['comparison' => '<', 'value' => 23],
        ];
        yield [
            ['value_type' => NumberType::class],
            ['comparison' => ComparisonType::EQ, 'value' => '23.23'],
            ['comparison' => '=', 'value' => 23.23],
        ];
        yield [
            ['value_type' => DateIntervalType::class],
            ['comparison' => ComparisonType::EQ, 'value' => ['years' => '1', 'months' => '2', 'days' => '3']],
            ['comparison' => '=', 'value' => new \DateInterval('P1Y2M3D')],
        ];
        yield [
            [
                'value_type' => ChoiceType::class,
                'value_type_options' => [
                    'choices' => ['ONE' => 1, 'TWO' => 2, 'THREE' => 3],
                ],
            ],
            ['comparison' => ComparisonType::GTE, 'value' => '2'],
            ['comparison' => '>=', 'value' => 2],
        ];
        yield [
            [
                'comparison_type_options' => ['type' => 'entity'],
                'value_type' => ColorType::class,
            ],
            ['comparison' => ComparisonType::EQ, 'value' => '#e66465'],
            ['comparison' => '=', 'value' => '#e66465'],
        ];
    }
}
