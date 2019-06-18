<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;

class BooleanFilterTypeTest extends FilterTypeTest
{
    /**
     * @dataProvider getDataProvider
     */
    public function testSubmit($submittedData, $data)
    {
        $form = $this->factory->create(BooleanFilterType::class);
        $form->submit($submittedData);

        $this->assertSame($data, $form->getData());
        $this->assertSame($submittedData, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testFilter($submittedData, $data, $paramName)
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
            ->with('o.foo = :'.$paramName)
            ->willReturn($qb)
        ;
        $qb->expects($this->once())
            ->method('setParameter')
            ->with($paramName, $data)
        ;

        $form = $this->factory->create(BooleanFilterType::class);
        $form->submit($submittedData);

        $filter = $this->filterRegistry->resolveType($form);
        $this->assertSame(BooleanFilterType::class, \get_class($filter));

        $filter->filter($qb, $form, ['property' => 'foo']);
    }

    public function getDataProvider(): iterable
    {
        yield ['1', true, 'foo_1'];
        yield ['0', false, 'foo_2'];
    }
}
