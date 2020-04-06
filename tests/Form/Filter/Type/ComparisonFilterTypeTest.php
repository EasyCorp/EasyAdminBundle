<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ComparisonFilterTypeTest extends FilterTypeTest
{
    protected const FILTER_TYPE = ComparisonFilterType::class;

    /**
     * @dataProvider getDataProvider
     */
    public function testSubmitAndFilter($submittedData, $data, $options, string $dql, array $params)
    {
        $form = $this->factory->create(static::FILTER_TYPE, null, $options);
        $form->submit($submittedData);
        $this->assertEquals($data, $form->getData());
        $this->assertEquals($data, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $filter = $this->filterRegistry->resolveType($form);
        $filter->filter($this->qb, $form, ['property' => 'foo']);
        $this->assertSame(static::FILTER_TYPE, \get_class($filter));
        $this->assertSame($dql, $this->qb->getDQL());
        $this->assertSameDoctrineParams($params, $this->qb->getParameters()->toArray());
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::LT, 'value' => '23'],
            ['comparison' => '<', 'value' => 23],
            ['value_type' => IntegerType::class],
            'SELECT o FROM Object o WHERE o.foo < :foo_1',
            [new Parameter('foo_1', 23, 'integer')],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => '23.23'],
            ['comparison' => '=', 'value' => 23.23],
            ['value_type' => NumberType::class],
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', 23.23, \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => ['years' => '1', 'months' => '2', 'days' => '3']],
            ['comparison' => '=', 'value' => new \DateInterval('P1Y2M3D')],
            ['value_type' => DateIntervalType::class],
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', new \DateInterval('P1Y2M3D'), 'dateinterval')],
        ];

        yield [
            ['comparison' => ComparisonType::GTE, 'value' => '2'],
            ['comparison' => '>=', 'value' => 2],
            [
                'value_type' => ChoiceType::class,
                'value_type_options' => [
                    'choices' => ['ONE' => 1, 'TWO' => 2, 'THREE' => 3],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo >= :foo_1',
            [new Parameter('foo_1', 2, 'integer')],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => '#e66465'],
            ['comparison' => '=', 'value' => '#e66465'],
            [
                'comparison_type_options' => ['type' => 'entity'],
                'value_type' => ColorType::class,
            ],
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', '#e66465', \PDO::PARAM_STR)],
        ];
    }
}
