<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Test\TypeTestCase;

class DateTimeFilterTypeTest extends TypeTestCase
{
    /**
     * @dataProvider submit
     */
    public function test(array $options, array $dataToSubmit, array $expectedData, string $expectedError = ''): void
    {
        $form = $this->factory->create(DateTimeFilterType::class, null, $options);
        $form->submit($dataToSubmit);

        if ($form->isValid()) {
            $this->assertEquals($expectedData, $form->getData());
            $this->assertEmpty($form->getExtraData());
            $this->assertTrue($form->isSynchronized());
            $this->assertInstanceOf(DateTimeFilterType::class, $form->getConfig()->getType()->getInnerType());
        } else {
            $this->assertSame($expectedError, $form->getTransformationFailure()->getMessage());
        }
    }

    public static function submit(): iterable
    {
        yield [
            [],
            ['comparison' => ComparisonType::EQ, 'value' => '2019-06-17 14:39:00', 'value2' => null],
            ['comparison' => '=', 'value' => new \DateTime('2019-06-17 14:39:00'), 'value2' => null],
        ];
        yield [
            ['value_type' => DateType::class],
            ['comparison' => ComparisonType::GT, 'value' => '2019-06-17 14:39:00', 'value2' => null],
            ['comparison' => '>', 'value' => '2019-06-17', 'value2' => null],
        ];
        yield [
            ['value_type' => TimeType::class],
            ['comparison' => ComparisonType::LTE, 'value' => '14:39', 'value2' => null],
            ['comparison' => '<=', 'value' => '14:39:00', 'value2' => null],
        ];
        yield [
            ['value_type' => TimeType::class],
            ['comparison' => ComparisonType::BETWEEN, 'value' => '14:39', 'value2' => '15:00'],
            ['comparison' => 'between', 'value' => '14:39:00', 'value2' => '15:00:00'],
        ];
        yield [
            ['value_type' => TimeType::class],
            ['comparison' => ComparisonType::BETWEEN, 'value' => '15:00', 'value2' => '14:39'],
            ['comparison' => 'between', 'value' => '14:39:00', 'value2' => '15:00:00'],
        ];
        yield [
            ['value_type' => TimeType::class],
            ['comparison' => ComparisonType::BETWEEN, 'value' => '15:00', 'value2' => null],
            ['comparison' => 'between', 'value' => '15:00:00', 'value2' => null],
            'Unable to reverse value for property path "ea_datetime_filter": Two values must be provided when "BETWEEN" comparison is selected.',
        ];
    }
}
