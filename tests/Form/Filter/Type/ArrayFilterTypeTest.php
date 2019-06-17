<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

class ArrayFilterTypeTest extends FilterTypeTest
{
    /**
     * @dataProvider getDataProvider
     */
    public function testSubmit($submittedData, $data)
    {
        $form = $this->factory->create(ArrayFilterType::class);
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
        if ([] === $data['value']) {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with(\sprintf('o.foo %s', $data['comparison']))
                ->willReturn($qb)
            ;
        } else {
            $orX = new Expr\Orx();
            $orX->add(\sprintf('o.foo %s :%s', $data['comparison'], $paramName));
            if (ComparisonType::NOT_CONTAINS === $data['comparison']) {
                $orX->add('o.foo IS NULL');
            }
            $qb->expects($this->once())
                ->method('andWhere')
                ->with($orX)
                ->willReturn($qb)
            ;
            $qb->expects($this->once())
                ->method('setParameter')
                ->with($paramName, '%"'.$data['value'][0].'"%')
            ;
        }

        $form = $this->factory->create(ArrayFilterType::class);
        $form->submit($submittedData);

        $filter = $this->filterRegistry->resolveType($form);
        $this->assertSame(ArrayFilterType::class, \get_class($filter));

        $filter->filter($qb, $form, ['property' => 'foo', 'dataType' => 'array']);
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => ['bar']],
            ['comparison' => 'like', 'value' => ['bar']],
            'foo_1',
        ];

        yield [
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => ['bar']],
            ['comparison' => 'not like', 'value' => ['bar']],
            'foo_2',
        ];

        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => []],
            'foo_3',
        ];
    }
}
