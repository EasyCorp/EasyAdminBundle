<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Form\Filter\Type;

use Doctrine\ORM\Query\Parameter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;

class BooleanFilterTypeTest extends FilterTypeTest
{
    protected const FILTER_TYPE = BooleanFilterType::class;

    /**
     * @dataProvider getDataProvider
     */
    public function testSubmitAndFilter($submittedData, $data, string $dql, array $params)
    {
        $form = $this->factory->create(static::FILTER_TYPE);
        $form->submit($submittedData);
        $this->assertSame($data, $form->getData());
        $this->assertSame($submittedData, $form->getViewData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());

        $filter = $this->filterRegistry->resolveType($form);
        $filter->filter($this->qb, $form, ['field' => 'foo']);
        $this->assertSame(static::FILTER_TYPE, $filter::class);
        $this->assertSame($dql, $this->qb->getDQL());
        $this->assertSameDoctrineParams($params, $this->qb->getParameters()->toArray());
    }

    public static function getDataProvider(): iterable
    {
        yield [
            '1',
            true,
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', true, 'boolean')],
        ];

        yield [
            '0',
            false,
            'SELECT o FROM Object o WHERE o.foo = :foo_1',
            [new Parameter('foo_1', false, 'boolean')],
        ];
    }
}
