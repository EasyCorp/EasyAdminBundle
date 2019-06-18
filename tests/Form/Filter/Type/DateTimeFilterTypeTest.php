<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\DateTimeFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class DateTimeFilterTypeTest extends FilterTypeTest
{
    /**
     * @dataProvider getDataProvider
     */
    public function testSubmit($submittedData, $data, $options)
    {
        $form = $this->factory->create(DateTimeFilterType::class, null, $options);
        $form->submit($submittedData);

        $this->assertEquals($data, $form->getData());
        $this->assertEquals($data, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testFilter($submittedData, $data, $options)
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

        $form = $this->factory->create(DateTimeFilterType::class, null, $options);
        $form->submit($submittedData);

        $filter = $this->filterRegistry->resolveType($form);
        $this->assertSame(ComparisonFilterType::class, \get_class($filter));

        $filter->filter($qb, $form, ['property' => 'foo']);
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::EQ, 'value' => '2019-06-17 14:39:00'],
            ['comparison' => '=', 'value' => new \DateTime('2019-06-17 14:39:00')],
            [],
        ];

        yield [
            ['comparison' => ComparisonType::GT, 'value' => '2019-06-17 14:39:00'],
            ['comparison' => '>', 'value' => '2019-06-17'],
            ['value_type' => DateType::class],
        ];

        yield [
            ['comparison' => ComparisonType::LTE, 'value' => '14:39'],
            ['comparison' => '<=', 'value' => '14:39:00'],
            ['value_type' => TimeType::class],
        ];
    }
}
