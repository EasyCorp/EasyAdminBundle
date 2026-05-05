<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\DateIntervalConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateIntervalField;

class DateIntervalFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new DateIntervalConfigurator();
    }

    public function testFieldWithoutValue(): void
    {
        $field = DateIntervalField::new('foo');
        $field->setFieldFqcn(DateIntervalField::class);
        $fieldDto = $this->configure($field);

        $this->assertNull($fieldDto->getFormattedValue());
    }

    public function testFieldWithoutCustomFormatLeavesValueForTemplateRendering(): void
    {
        $field = DateIntervalField::new('foo')->setValue(new \DateInterval('P2Y4DT6H8M'));
        $field->setFieldFqcn(DateIntervalField::class);
        $fieldDto = $this->configure($field);

        // sentinel empty string keeps CommonPostConfigurator from swapping the template
        $this->assertSame('', $fieldDto->getFormattedValue());
        $this->assertInstanceOf(\DateInterval::class, $fieldDto->getValue());
    }

    public function testFieldWithPerFieldFormat(): void
    {
        $field = DateIntervalField::new('foo')
            ->setValue(new \DateInterval('P1Y2M3D'))
            ->setFormat('%y years, %m months, %d days');
        $field->setFieldFqcn(DateIntervalField::class);
        $fieldDto = $this->configure($field);

        $this->assertSame('1 years, 2 months, 3 days', $fieldDto->getFormattedValue());
    }

    public function testTemplateRendersLocalizedPluralizedString(): void
    {
        $field = DateIntervalField::new('foo')->setValue(new \DateInterval('P1Y2M3DT4H5M6S'));
        $field->setFieldFqcn(DateIntervalField::class);
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        $this->assertStringContainsString('1 year', $html);
        $this->assertStringContainsString('2 months', $html);
        $this->assertStringContainsString('3 days', $html);
        $this->assertStringContainsString('4 hours', $html);
        $this->assertStringContainsString('5 minutes', $html);
        $this->assertStringContainsString('6 seconds', $html);
        $this->assertStringContainsString('datetime="P1Y2M3DT4H5M6S"', $html);
    }

    public function testTemplateRendersZeroDurationAsEmptyLabel(): void
    {
        $field = DateIntervalField::new('foo')->setValue(new \DateInterval('PT0S'));
        $field->setFieldFqcn(DateIntervalField::class);
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        $this->assertStringContainsString('0 seconds', $html);
    }
}