<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Parameter;
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
    public function testSubmitAndFilter($submittedData, $data, $options, string $dql, array $params)
    {
        $form = $this->factory->create(DateTimeFilterType::class, null, $options);
        $form->submit($submittedData);
        $this->assertEquals($data, $form->getData());
        $this->assertEquals($data, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $filter = $this->filterRegistry->resolveType($form);
        $filter->filter($this->qb, $form, ['property' => 'foo']);
        $this->assertSame(ComparisonFilterType::class, \get_class($filter));
        $this->assertSame($dql, $this->qb->getDQL());
        $this->assertEquals($params, $this->qb->getParameters()->toArray());
    }

    public function getDataProvider(): iterable
    {
        yield [
            ['comparison' => ComparisonType::EQ, 'value' => '2019-06-17 14:39:00'],
            ['comparison' => '=', 'value' => new \DateTime('2019-06-17 14:39:00')],
            [],
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', new \DateTime('2019-06-17 14:39:00'), 'datetime')],
        ];

        yield [
            ['comparison' => ComparisonType::GT, 'value' => '2019-06-17 14:39:00'],
            ['comparison' => '>', 'value' => '2019-06-17'],
            ['value_type' => DateType::class],
            'SELECT o FROM Object o WHERE o.foo > :foo_1',
            [new Parameter('foo_1', '2019-06-17', \PDO::PARAM_STR)],
        ];

        yield [
            ['comparison' => ComparisonType::LTE, 'value' => '14:39'],
            ['comparison' => '<=', 'value' => '14:39:00'],
            ['value_type' => TimeType::class],
            'SELECT o FROM Object o WHERE o.foo <= :foo_1',
            [new Parameter('foo_1', '14:39:00', \PDO::PARAM_STR)],
        ];
    }
}
