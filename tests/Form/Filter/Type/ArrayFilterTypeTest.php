<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

class ArrayFilterTypeTest extends FilterTypeTest
{
    protected const FILTER_TYPE = ArrayFilterType::class;

    /**
     * @dataProvider getDataProvider
     */
    public function testSubmitAndFilter($submittedData, $data, array $options, string $dql, array $params)
    {
        $form = $this->factory->create(static::FILTER_TYPE, null, $options);
        $form->submit($submittedData);
        $this->assertSame($data, $form->getData());
        $this->assertSame($submittedData, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $filter = $this->filterRegistry->resolveType($form);
        $filter->filter($this->qb, $form, ['property' => 'foo', 'dataType' => 'array']);
        $this->assertSame(static::FILTER_TYPE, \get_class($filter));
        $this->assertSame($dql, $this->qb->getDQL());
        $this->assertEquals($params, $this->qb->getParameters()->toArray());
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => ['bar']],
            ['comparison' => 'like', 'value' => ['bar']],
            [],
            'SELECT o FROM Object o WHERE o.foo like :foo_1',
            [new Parameter('foo_1', '%"bar"%', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => ['foo', 'bar']],
            ['comparison' => 'not like', 'value' => ['foo', 'bar']],
            [],
            'SELECT o FROM Object o WHERE o.foo not like :foo_1 OR o.foo not like :foo_2 OR o.foo IS NULL',
            [
                new Parameter('foo_1', '%"foo"%', \PDO::PARAM_STR),
                new Parameter('foo_2', '%"bar"%', \PDO::PARAM_STR),
            ],
        ];

        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => []],
            [],
            'SELECT o FROM Object o WHERE o.foo IS NULL',
            [],
        ];

        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
            [
                'value_type_options' => [
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo IS NULL',
            [],
        ];

        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => 'b'],
            ['comparison' => 'like', 'value' => ['b']],
            [
                'value_type_options' => [
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo like :foo_1',
            [new Parameter('foo_1', '%"b"%', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => ['a', 'c']],
            ['comparison' => 'not like', 'value' => ['a', 'c']],
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo not like :foo_1 OR o.foo not like :foo_2 OR o.foo IS NULL',
            [
                new Parameter('foo_1', '%"a"%', \PDO::PARAM_STR),
                new Parameter('foo_2', '%"c"%', \PDO::PARAM_STR),
            ],
        ];
    }
}
