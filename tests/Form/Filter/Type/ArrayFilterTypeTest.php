<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ArrayFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;

class ArrayFilterTypeTest extends FilterTypeTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // reset counter (only for test purpose)
        $m = new \ReflectionProperty(ArrayFilterType::class, 'uniqueAliasId');
        $m->setAccessible(true);
        $m->setValue(0);
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testSubmit($submittedData, $data, array $options = [])
    {
        $form = $this->factory->create(ArrayFilterType::class, null, $options);
        $form->submit($submittedData);

        $this->assertSame($data, $form->getData());
        $this->assertSame($submittedData, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testFilter($submittedData, $data, array $options = [])
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $qb->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['o'])
        ;
        if (null === $data['value'] || [] === $data['value']) {
            $qb->expects($this->once())
                ->method('andWhere')
                ->with(\sprintf('o.foo %s', $data['comparison']))
                ->willReturn($qb)
            ;
        } else {
            $orX = new Expr\Orx();
            $orX->add(\sprintf('o.foo %s :foo_1', $data['comparison']));
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
                ->with('foo_1', '%"'.$data['value'][0].'"%')
            ;
        }

        $form = $this->factory->create(ArrayFilterType::class, null, $options);
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
        ];

        yield [
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => ['bar']],
            ['comparison' => 'not like', 'value' => ['bar']],
        ];

        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => []],
            ['comparison' => 'IS NULL', 'value' => []],
        ];

        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => null],
            ['comparison' => 'IS NULL', 'value' => null],
            [
                'value_type_options' => [
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
        ];

        yield [
            ['comparison' => ComparisonType::CONTAINS, 'value' => 'b'],
            ['comparison' => 'like', 'value' => ['b']],
            [
                'value_type_options' => [
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
        ];

        yield [
            ['comparison' => ComparisonType::NOT_CONTAINS, 'value' => ['c']],
            ['comparison' => 'not like', 'value' => ['c']],
            [
                'value_type_options' => [
                    'multiple' => true,
                    'choices' => ['a' => 'a', 'b' => 'b', 'c' => 'c'],
                ],
            ],
        ];
    }
}
