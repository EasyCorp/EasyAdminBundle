<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\DateTimeConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Trait\DateTimeWidgetTestTrait;

class DateTimeFieldTest extends AbstractFieldTest
{
    use DateTimeWidgetTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new DateTimeConfigurator(new IntlFormatter());
    }

    protected function createFieldForWidgetTest(): FieldInterface
    {
        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);

        return $field;
    }

    protected function getWidgetOptionKey(): string
    {
        return DateTimeField::OPTION_WIDGET;
    }

    public function testFieldWithWrongTimezone(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setTimezone('this-timezone-does-not-exist');
    }

    public function testFieldWithoutTimezone(): void
    {
        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);
        $fieldDto = $this->configure($field);

        $this->assertNull($fieldDto->getCustomOption(DateTimeField::OPTION_TIMEZONE));
    }

    public function testFieldWithTimezone(): void
    {
        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);
        $field->setTimezone('Europe/Madrid');
        $fieldDto = $this->configure($field);

        $this->assertSame('Europe/Madrid', $fieldDto->getCustomOption(DateTimeField::OPTION_TIMEZONE));
    }

    public function testFieldWithWrongFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);
        $field->setFormat(DateTimeField::FORMAT_NONE);
    }

    public function testFieldWithEmptyDateFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);
        $field->setFormat('');
    }

    public function testFieldWithEmptyDateAndTimeFormats(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);
        $field->setFormat('', '');
    }

    public function testFieldWithNoneDateAndTimeFormats(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);
        $field->setFormat(DateTimeField::FORMAT_NONE, DateTimeField::FORMAT_NONE);
    }

    public function testFieldWithPredefinedFormat(): void
    {
        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);
        $field->setFormat(DateTimeField::FORMAT_LONG);
        $fieldDto = $this->configure($field);

        $this->assertSame(DateTimeField::FORMAT_LONG, $fieldDto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        $this->assertSame('January 16, 2015', $fieldDto->getFormattedValue());
    }

    public function testFieldWithCustomPattern(): void
    {
        $field = DateTimeField::new('foo')->setValue(new \DateTime('2015-01-16'));
        $field->setFieldFqcn(DateTimeField::class);
        $field->setFormat('HH:mm:ss ZZZZ a');
        $fieldDto = $this->configure($field);

        $this->assertSame('HH:mm:ss ZZZZ a', $fieldDto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        // some ICU versions return "GMT" while others return "GMT+00:00"
        $this->assertMatchesRegularExpression('/^00:00:00 GMT(\+00:00)? AM$/', $fieldDto->getFormattedValue());
    }
}
