<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

class TextFilterTypeTest extends FilterTypeTest
{
    /**
     * @dataProvider getDataProvider
     */
    public function testSubmitAndFilter($submittedData, $data, string $dql, array $params)
    {
        $form = $this->factory->create(TextFilterType::class);
        $form->submit($submittedData);
        $this->assertSame($data, $form->getData());
        $this->assertSame($submittedData, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $filter = $this->filterRegistry->resolveType($form);
        $filter->filter($this->qb, $form, ['property' => 'foo']);
        $this->assertSame(ComparisonFilterType::class, \get_class($filter));
        $this->assertSame($dql, $this->qb->getDQL());
        $this->assertSameDoctrineParams($params, $this->qb->getParameters()->toArray());
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => '%abc%'],
            'SELECT o FROM Object o WHERE o.foo like :foo_1',
            [new Parameter('foo_1', '%abc%', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => 'abc'],
            ['comparison' => 'not like', 'value' => '%abc%'],
            'SELECT o FROM Object o WHERE o.foo not like :foo_1',
            [new Parameter('foo_1', '%abc%', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::STARTS_WITH, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => 'abc%'],
            'SELECT o FROM Object o WHERE o.foo like :foo_1',
            [new Parameter('foo_1', 'abc%', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::ENDS_WITH, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => '%abc'],
            'SELECT o FROM Object o WHERE o.foo like :foo_1',
            [new Parameter('foo_1', '%abc', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => 'abc'],
            ['comparison' => '=', 'value' => 'abc'],
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', 'abc', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => 'abc'],
            ['comparison' => '!=', 'value' => 'abc'],
            'SELECT o FROM Object o WHERE o.foo != :foo_1',
            [new Parameter('foo_1', 'abc', \PDO::PARAM_STR)],
        ];
    }
}
