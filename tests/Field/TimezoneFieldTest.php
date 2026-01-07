<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\TimezoneConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;

class TimezoneFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new TimezoneConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = TimezoneField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertSame('ea-autocomplete', $fieldDto->getFormTypeOption('attr.data-ea-widget'));
        self::assertTrue($fieldDto->getFormTypeOption('intl'));
        self::assertFalse($fieldDto->getFormTypeOption('choice_translation_domain'));
        self::assertSame(TimezoneType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-timezone', $fieldDto->getCssClass());
    }

    public function testFieldWithValue(): void
    {
        $field = TimezoneField::new('foo');
        $field->setValue('Europe/Madrid');
        $fieldDto = $this->configure($field);

        self::assertSame('Europe/Madrid', $fieldDto->getValue());
    }

    public function testFieldWithNullValue(): void
    {
        $field = TimezoneField::new('foo');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFormTypeOptionsAreNotOverriddenIfAlreadySet(): void
    {
        $field = TimezoneField::new('foo');
        $field->setFormTypeOption('attr.data-ea-widget', 'custom-widget');
        $field->setFormTypeOption('intl', false);
        $field->setFormTypeOption('choice_translation_domain', 'messages');
        $fieldDto = $this->configure($field);

        self::assertSame('custom-widget', $fieldDto->getFormTypeOption('attr.data-ea-widget'));
        self::assertFalse($fieldDto->getFormTypeOption('intl'));
        self::assertSame('messages', $fieldDto->getFormTypeOption('choice_translation_domain'));
    }
}
