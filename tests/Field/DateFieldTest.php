<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\DateTimeConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;

class DateFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $intlFormatterMock = $this->getMockBuilder(IntlFormatter::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['formatDate'])
            ->getMock();
        $intlFormatterMock->method('formatDate')->willReturnCallback(
            static function ($value, ?string $dateFormat = 'medium', string $pattern = '', $timezone = null, string $calendar = 'gregorian', ?string $locale = null) { return sprintf('value: %s | dateFormat: %s | pattern: %s | timezone: %s | calendar: %s | locale: %s', $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value, $dateFormat, $pattern, $timezone, $calendar, $locale); }
        );

        $this->configurator = new DateTimeConfigurator($intlFormatterMock);
    }

    public function testFieldWithWrongTimezone()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateField::new('foo');
        $field->setTimezone('this-timezone-does-not-exist');
    }

    public function testFieldWithoutTimezone()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $fieldDto = $this->configure($field);

        $this->assertNull($fieldDto->getCustomOption(DateTimeField::OPTION_TIMEZONE));
    }

    public function testFieldWithTimezone()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->setTimezone('Europe/Madrid');
        $fieldDto = $this->configure($field);

        $this->assertSame('Europe/Madrid', $fieldDto->getCustomOption(DateTimeField::OPTION_TIMEZONE));
    }

    public function testFieldWithWrongFormat()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->setFormat(DateTimeField::FORMAT_NONE);
    }

    public function testFieldWithEmptyFormat()
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->setFormat('');
    }

    public function testFieldWithPredefinedFormat()
    {
        $field = DateField::new('foo')->setValue(new \DateTime('2006-01-02 15:04:05'));
        $field->setFieldFqcn(DateField::class);
        $field->setFormat(DateTimeField::FORMAT_LONG);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::FORMAT_LONG, $fieldDto->getCustomOption(DateField::OPTION_DATE_PATTERN));
        $this->assertSame('value: 2006-01-02 15:04:05 | dateFormat: long | pattern:  | timezone:  | calendar: gregorian | locale: ', $fieldDto->getFormattedValue());
    }

    public function testFieldWithCustomPattern()
    {
        $field = DateField::new('foo')->setValue(new \DateTime('2006-01-02 15:04:05'));
        $field->setFieldFqcn(DateField::class);
        $field->setFormat('HH:mm:ss ZZZZ a');
        $fieldDto = $this->configure($field);

        $this->assertSame('HH:mm:ss ZZZZ a', $fieldDto->getCustomOption(DateField::OPTION_DATE_PATTERN));
        $this->assertSame('value: 2006-01-02 15:04:05 | dateFormat:  | pattern: HH:mm:ss ZZZZ a | timezone:  | calendar: gregorian | locale: ', $fieldDto->getFormattedValue());
    }

    public function testFieldDefaultWidget()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $fieldDto->getCustomOption(DateField::OPTION_WIDGET));
    }

    public function testFieldRenderAsNativeWidget()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->renderAsNativeWidget();
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $fieldDto->getCustomOption(DateField::OPTION_WIDGET));
        $this->assertSame('single_text', $fieldDto->getFormTypeOption('widget'));
        $this->assertTrue($fieldDto->getFormTypeOption('html5'));
    }

    public function testFieldRenderAsNotNativeWidget()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->renderAsNativeWidget(false);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_CHOICE, $fieldDto->getCustomOption(DateField::OPTION_WIDGET));
    }

    public function testFieldRenderAsChoice()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->renderAsChoice();
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_CHOICE, $fieldDto->getCustomOption(DateField::OPTION_WIDGET));
        $this->assertSame('choice', $fieldDto->getFormTypeOption('widget'));
        $this->assertTrue($fieldDto->getFormTypeOption('html5'));
    }

    public function testFieldRenderAsNotChoice()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->renderAsChoice(false);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $fieldDto->getCustomOption(DateField::OPTION_WIDGET));
    }

    public function testFieldRenderAsText()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->renderAsText();
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_TEXT, $fieldDto->getCustomOption(DateField::OPTION_WIDGET));
        $this->assertSame('single_text', $fieldDto->getFormTypeOption('widget'));
        $this->assertFalse($fieldDto->getFormTypeOption('html5'));
    }

    public function testFieldRenderAsNotText()
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->renderAsText(false);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::WIDGET_NATIVE, $fieldDto->getCustomOption(DateField::OPTION_WIDGET));
    }
}
