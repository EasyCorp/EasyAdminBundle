<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Trait;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

/**
 * Trait for testing widget rendering options on Date, DateTime, and Time fields.
 *
 * Classes using this trait must implement:
 * - createFieldForWidgetTest(): creates a field instance for testing
 * - configure(FieldInterface $field): FieldDto - configures the field and returns the DTO
 * - getWidgetOptionKey(): string - returns the option key for widget (e.g., DateField::OPTION_WIDGET)
 */
trait DateTimeWidgetTestTrait
{
    abstract protected function createFieldForWidgetTest(): FieldInterface;

    abstract protected function getWidgetOptionKey(): string;

    public function testFieldDefaultWidget(): void
    {
        $field = $this->createFieldForWidgetTest();
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $fieldDto->getCustomOption($this->getWidgetOptionKey()));
    }

    public function testFieldRenderAsNativeWidget(): void
    {
        $field = $this->createFieldForWidgetTest();
        $field->renderAsNativeWidget();
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $fieldDto->getCustomOption($this->getWidgetOptionKey()));
        $this->assertSame('single_text', $fieldDto->getFormTypeOption('widget'));
        $this->assertTrue($fieldDto->getFormTypeOption('html5'));
    }

    public function testFieldRenderAsNotNativeWidget(): void
    {
        $field = $this->createFieldForWidgetTest();
        $field->renderAsNativeWidget(false);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_CHOICE, $fieldDto->getCustomOption($this->getWidgetOptionKey()));
    }

    public function testFieldRenderAsChoice(): void
    {
        $field = $this->createFieldForWidgetTest();
        $field->renderAsChoice();
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_CHOICE, $fieldDto->getCustomOption($this->getWidgetOptionKey()));
        $this->assertSame('choice', $fieldDto->getFormTypeOption('widget'));
        $this->assertTrue($fieldDto->getFormTypeOption('html5'));
    }

    public function testFieldRenderAsNotChoice(): void
    {
        $field = $this->createFieldForWidgetTest();
        $field->renderAsChoice(false);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $fieldDto->getCustomOption($this->getWidgetOptionKey()));
    }

    public function testFieldRenderAsText(): void
    {
        $field = $this->createFieldForWidgetTest();
        $field->renderAsText();
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_TEXT, $fieldDto->getCustomOption($this->getWidgetOptionKey()));
        $this->assertSame('single_text', $fieldDto->getFormTypeOption('widget'));
        $this->assertFalse($fieldDto->getFormTypeOption('html5'));
    }

    public function testFieldRenderAsNotText(): void
    {
        $field = $this->createFieldForWidgetTest();
        $field->renderAsText(false);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $fieldDto->getCustomOption($this->getWidgetOptionKey()));
    }
}
