<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Filter;

use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use Symfony\Component\Form\Test\TypeTestCase;

class BooleanFilterTypeTest extends TypeTestCase
{
    /**
     * @dataProvider submit
     */
    public function test(string $dataToSubmit, bool $expectedData): void
    {
        $form = $this->factory->create(BooleanFilterType::class);
        $form->submit($dataToSubmit);

        $this->assertSame($dataToSubmit, $form->getViewData());
        $this->assertSame($expectedData, $form->getData());
        $this->assertEmpty($form->getExtraData());
        $this->assertTrue($form->isSynchronized());
        $this->assertInstanceOf(BooleanFilterType::class, $form->getConfig()->getType()->getInnerType());
    }

    public static function submit(): iterable
    {
        yield ['1', true];
        yield ['0', false];
    }
}
