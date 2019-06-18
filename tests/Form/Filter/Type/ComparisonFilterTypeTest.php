<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ComparisonFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ComparisonFilterTypeTest extends FilterTypeTest
{
    /**
     * @dataProvider getDataProvider
     */
    public function testSubmit($submittedData, $data, $options)
    {
        $form = $this->factory->create(ComparisonFilterType::class, null, $options);
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

        $form = $this->factory->create(ComparisonFilterType::class, null, $options);
        $form->submit($submittedData);

        $filter = $this->filterRegistry->resolveType($form);
        $this->assertSame(ComparisonFilterType::class, \get_class($filter));

        $filter->filter($qb, $form, ['property' => 'foo']);
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::LT, 'value' => '23'],
            ['comparison' => '<', 'value' => 23],
            ['value_type' => IntegerType::class],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => '23.23'],
            ['comparison' => '=', 'value' => 23.23],
            ['value_type' => NumberType::class],
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => ['years' => '1', 'months' => '2', 'days' => '3']],
            ['comparison' => '=', 'value' => new \DateInterval('P1Y2M3D')],
            ['value_type' => DateIntervalType::class],
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
        ];

        yield [
            ['comparison' => ComparisonType::EQ, 'value' => '#e66465'],
            ['comparison' => '=', 'value' => '#e66465'],
            [
                'comparison_type_options' => ['type' => 'entity'],
                'value_type' => ColorType::class,
            ],
        ];
    }
}
