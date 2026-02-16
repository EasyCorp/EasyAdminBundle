<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\DateTimeConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Trait\DateTimeWidgetTestTrait;

class DateFieldTest extends AbstractFieldTest
{
    use DateTimeWidgetTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new DateTimeConfigurator(new IntlFormatter());
    }

    protected function createFieldForWidgetTest(): FieldInterface
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);

        return $field;
    }

    protected function getWidgetOptionKey(): string
    {
        return DateField::OPTION_WIDGET;
    }

    public function testFieldWithWrongTimezone(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateField::new('foo');
        $field->setTimezone('this-timezone-does-not-exist');
    }

    public function testFieldWithoutTimezone(): void
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $fieldDto = $this->configure($field);

        $this->assertNull($fieldDto->getCustomOption(DateTimeField::OPTION_TIMEZONE));
    }

    public function testFieldWithTimezone(): void
    {
        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->setTimezone('Europe/Madrid');
        $fieldDto = $this->configure($field);

        $this->assertSame('Europe/Madrid', $fieldDto->getCustomOption(DateTimeField::OPTION_TIMEZONE));
    }

    public function testFieldWithWrongFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->setFormat(DateTimeField::FORMAT_NONE);
    }

    public function testFieldWithEmptyFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateField::new('foo');
        $field->setFieldFqcn(DateField::class);
        $field->setFormat('');
    }

    public function testFieldWithPredefinedFormat(): void
    {
        $field = DateField::new('foo')->setValue(new \DateTime('2006-01-02 15:04:05'));
        $field->setFieldFqcn(DateField::class);
        $field->setFormat(DateTimeField::FORMAT_LONG);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::FORMAT_LONG, $fieldDto->getCustomOption(DateField::OPTION_DATE_PATTERN));
        $this->assertSame('January 2, 2006', $fieldDto->getFormattedValue());
    }

    public function testFieldWithCustomPattern(): void
    {
        $field = DateField::new('foo')->setValue(new \DateTime('2006-01-02 15:04:05'));
        $field->setFieldFqcn(DateField::class);
        $field->setFormat('HH:mm:ss ZZZZ a');
        $fieldDto = $this->configure($field);

        $this->assertSame('HH:mm:ss ZZZZ a', $fieldDto->getCustomOption(DateField::OPTION_DATE_PATTERN));
        // some ICU versions return "GMT" while others return "GMT+00:00"
        $this->assertMatchesRegularExpression('/^15:04:05 GMT(\+00:00)? PM$/', $fieldDto->getFormattedValue());
    }
}
