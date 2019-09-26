<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class DateTimeFilterTypeTest extends FilterTypeTest
{
    protected const FILTER_TYPE = DateTimeFilterType::class;

    /**
     * @dataProvider getDataProvider
     */
    public function testSubmitAndFilter($submittedData, $data, $options, string $dql, array $params, string $expectedError = '')
    {
        $form = $this->factory->create(static::FILTER_TYPE, null, $options);
        $form->submit($submittedData);
        $this->assertTrue($form->isSubmitted());
        if ($form->isValid()) {
            $this->assertEquals($data, $form->getData());
            $this->assertEmpty($form->getExtraData());
            $this->assertTrue($form->isSynchronized());

            $filter = $this->filterRegistry->resolveType($form);
            $filter->filter($this->qb, $form, ['property' => 'foo']);
            $this->assertSame(static::FILTER_TYPE, \get_class($filter));
            $this->assertSame($dql, $this->qb->getDQL());
            $this->assertSameDoctrineParams($params, $this->qb->getParameters()->toArray());
        } else {
            $this->assertSame($expectedError, $form->getTransformationFailure()->getMessage());
        }
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::EQ, 'value' => '2019-06-17 14:39:00', 'value2' => null],
            ['comparison' => '=', 'value' => new \DateTime('2019-06-17 14:39:00'), 'value2' => null],
            [],
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', new \DateTime('2019-06-17 14:39:00'), 'datetime')],
        ];

        yield [
            ['comparison' => ComparisonType::GT, 'value' => '2019-06-17 14:39:00', 'value2' => null],
            ['comparison' => '>', 'value' => '2019-06-17', 'value2' => null],
            ['value_type' => DateType::class],
            'SELECT o FROM Object o WHERE o.foo > :foo_1',
            [new Parameter('foo_1', '2019-06-17', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::LTE, 'value' => '14:39', 'value2' => null],
            ['comparison' => '<=', 'value' => '14:39:00', 'value2' => null],
            ['value_type' => TimeType::class],
            'SELECT o FROM Object o WHERE o.foo <= :foo_1',
            [new Parameter('foo_1', '14:39:00', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::BETWEEN, 'value' => '14:39', 'value2' => '15:00'],
            ['comparison' => 'between', 'value' => '14:39:00', 'value2' => '15:00:00'],
            ['value_type' => TimeType::class],
            'SELECT o FROM Object o WHERE o.foo BETWEEN :foo_1 and :foo_2',
            [
                new Parameter('foo_1', '14:39:00', \PDO::PARAM_STR),
                new Parameter('foo_2', '15:00:00', \PDO::PARAM_STR),
            ],
        ];

        yield [
            ['comparison' => ComparisonType::BETWEEN, 'value' => '15:00', 'value2' => '14:39'],
            ['comparison' => 'between', 'value' => '14:39:00', 'value2' => '15:00:00'],
            ['value_type' => TimeType::class],
            'SELECT o FROM Object o WHERE o.foo BETWEEN :foo_1 and :foo_2',
            [
                new Parameter('foo_1', '14:39:00', \PDO::PARAM_STR),
                new Parameter('foo_2', '15:00:00', \PDO::PARAM_STR),
            ],
        ];

        yield [
            ['comparison' => ComparisonType::BETWEEN, 'value' => '15:00', 'value2' => null],
            ['comparison' => 'between', 'value' => '15:00:00', 'value2' => null],
            ['value_type' => TimeType::class],
            '',
            [],
            'Unable to reverse value for property path "easyadmin_datetime_filter": Two values must be provided when "BETWEEN" comparison is selected.',
        ];
    }
}
