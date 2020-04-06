<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

class ChoiceFilterTypeTest extends FilterTypeTest
{
    protected const FILTER_TYPE = ChoiceFilterType::class;

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
        $filter->filter($this->qb, $form, ['property' => 'foo']);
        $this->assertSame(static::FILTER_TYPE, \get_class($filter));
        $this->assertSame($dql, $this->qb->getDQL());
        $this->assertSameDoctrineParams($params, $this->qb->getParameters()->toArray());
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::EQ, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
            [
                'value_type_options' => [
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo IS NULL',
            [],
        ];

        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => null],
            ['comparison' => 'IS NOT NULL', 'value' => null],
            [
                'value_type_options' => [
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo IS NOT NULL',
            [],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => 'a'],
            ['comparison' => '=', 'value' => 'a'],
            [
                'value_type_options' => [
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo = (:foo_1)',
            [new Parameter('foo_1', 'a', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => 'b'],
            ['comparison' => '!=', 'value' => 'b'],
            [
                'value_type_options' => [
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo != (:foo_1) OR o.foo IS NULL',
            [new Parameter('foo_1', 'b', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => []],
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo IS NULL',
            [],
        ];

        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => []],
            ['comparison' => 'IS NOT NULL', 'value' => []],
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo IS NOT NULL',
            [],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => ['a', 'b']],
            ['comparison' => 'IN', 'value' => ['a', 'b']],
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo IN (:foo_1)',
            [new Parameter('foo_1', ['a', 'b'], Connection::PARAM_STR_ARRAY)],
        ];

        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => ['b', 'c']],
            ['comparison' => 'NOT IN', 'value' => ['b', 'c']],
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a', 'b', 'c'],
                ],
            ],
            'SELECT o FROM Object o WHERE o.foo NOT IN (:foo_1)',
            [new Parameter('foo_1', ['b', 'c'], Connection::PARAM_STR_ARRAY)],
        ];
    }
}
