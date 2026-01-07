<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\EmailConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class EmailFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new EmailConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = EmailField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame('email', $fieldDto->getFormTypeOption('attr.inputmode'));
        self::assertSame(EmailType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-email', $fieldDto->getCssClass());
    }

    public function testFieldWithValue(): void
    {
        $field = EmailField::new('foo');
        $field->setValue('test@example.com');
        $fieldDto = $this->configure($field);

        self::assertSame('test@example.com', $fieldDto->getValue());
    }

    public function testFieldWithNullValue(): void
    {
        $field = EmailField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testInputModeIsNotOverriddenIfAlreadySet(): void
    {
        $field = EmailField::new('foo');
        $field->setFormTypeOption('attr.inputmode', 'text');
        $fieldDto = $this->configure($field);

        self::assertSame('text', $fieldDto->getFormTypeOption('attr.inputmode'));
    }
}
