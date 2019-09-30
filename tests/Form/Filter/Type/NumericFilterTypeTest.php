<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\NumericFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class NumericFilterTypeTest extends FilterTypeTest
{
    protected const FILTER_TYPE = NumericFilterType::class;

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
            ['comparison' => ComparisonType::EQ, 'value' => '23', 'value2' => null],
            ['comparison' => '=', 'value' => 23, 'value2' => null],
            ['value_type' => IntegerType::class],
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', 23, 'integer')],
        ];

        yield [
            ['comparison' => ComparisonType::GT, 'value' => '23.23', 'value2' => null],
            ['comparison' => '>', 'value' => 23.23, 'value2' => null],
            [],
            'SELECT o FROM Object o WHERE o.foo > :foo_1',
            [new Parameter('foo_1', 23.23, \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::BETWEEN, 'value' => '23', 'value2' => '32'],
            ['comparison' => 'between', 'value' => '23', 'value2' => '32'],
            ['value_type' => IntegerType::class],
            'SELECT o FROM Object o WHERE o.foo BETWEEN :foo_1 and :foo_2',
            [
                new Parameter('foo_1', 23, 'integer'),
                new Parameter('foo_2', 32, 'integer'),
            ],
        ];

        yield [
            ['comparison' => ComparisonType::BETWEEN, 'value' => '32', 'value2' => '23'],
            ['comparison' => 'between', 'value' => '23', 'value2' => '32'],
            ['value_type' => IntegerType::class],
            'SELECT o FROM Object o WHERE o.foo BETWEEN :foo_1 and :foo_2',
            [
                new Parameter('foo_1', 23, 'integer'),
                new Parameter('foo_2', 32, 'integer'),
            ],
        ];

        yield [
            ['comparison' => ComparisonType::BETWEEN, 'value' => '23.32', 'value2' => null],
            ['comparison' => 'between', 'value' => '23.32', 'value2' => null],
            [],
            '',
            [],
            'Unable to reverse value for property path "easyadmin_numeric_filter": Two values must be provided when "BETWEEN" comparison is selected.',
        ];
    }
}
