<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TelephoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use Symfony\Component\Form\Extension\Core\Type\TelType;

class TelephoneFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new TelephoneConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = TelephoneField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame('tel', $fieldDto->getFormTypeOption('attr.inputmode'));
        self::assertSame(TelType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-telephone', $fieldDto->getCssClass());
    }

    public function testFieldWithValue(): void
    {
        $field = TelephoneField::new('foo');
        $field->setValue('+1234567890');
        $fieldDto = $this->configure($field);

        self::assertSame('+1234567890', $fieldDto->getValue());
    }

    public function testFieldWithNullValue(): void
    {
        $field = TelephoneField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testInputModeIsNotOverriddenIfAlreadySet(): void
    {
        $field = TelephoneField::new('foo');
        $field->setFormTypeOption('attr.inputmode', 'text');
        $fieldDto = $this->configure($field);

        self::assertSame('text', $fieldDto->getFormTypeOption('attr.inputmode'));
    }
}
