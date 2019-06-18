<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\TextFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

class TextFilterTypeTest extends FilterTypeTest
{
    /**
     * @dataProvider getDataProvider
     */
    public function testSubmit($submittedData, $data)
    {
        $form = $this->factory->create(TextFilterType::class);
        $form->submit($submittedData);

        $this->assertSame($data, $form->getData());
        $this->assertSame($submittedData, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testFilter($submittedData, $data)
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['o'])
        ;
        $qb->expects($this->once())
            ->method('andWhere')
            ->with(\sprintf('o.foo %s :foo_1', $data['comparison']))
            ->willReturn($qb)
        ;
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('foo_1', $data['value'])
        ;

        $form = $this->factory->create(TextFilterType::class);
        $form->submit($submittedData);

        $filter = $this->filterRegistry->resolveType($form);
        $this->assertSame(ComparisonFilterType::class, \get_class($filter));

        $filter->filter($qb, $form, ['property' => 'foo']);
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => '%abc%'],
        ];

        yield [
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => 'abc'],
            ['comparison' => 'not like', 'value' => '%abc%'],
        ];

        yield [
            ['comparison' => ComparisonType::STARTS_WITH, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => 'abc%'],
        ];

        yield [
            ['comparison' => ComparisonType::ENDS_WITH, 'value' => 'abc'],
            ['comparison' => 'like', 'value' => '%abc'],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => 'abc'],
            ['comparison' => '=', 'value' => 'abc'],
        ];

        yield [
            ['comparison' => ComparisonType::NEQ, 'value' => 'abc'],
            ['comparison' => '!=', 'value' => 'abc'],
        ];
    }
}
