<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use Symfony\Component\Form\Extension\Core\Type\ColorType;

class ColorFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // ColorField has no dedicated configurator, but we need to set formattedValue like CommonPreConfigurator does
        $this->configurator = new class implements FieldConfiguratorInterface {
            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return ColorField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                // set formattedValue to value (like CommonPreConfigurator does for most fields)
                if (null === $field->getFormattedValue()) {
                    $field->setFormattedValue($field->getValue());
                }
            }
        };
    }

    public function testDefaultOptions(): void
    {
        $field = ColorField::new('color');
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ColorField::OPTION_SHOW_SAMPLE));
        self::assertFalse($fieldDto->getCustomOption(ColorField::OPTION_SHOW_VALUE));
        self::assertSame(ColorType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-color', $fieldDto->getCssClass());
        self::assertSame('crud/field/color', $fieldDto->getTemplateName());
    }

    public function testFieldWithValue(): void
    {
        $field = ColorField::new('color');
        $field->setValue('#ff5733');
        $fieldDto = $this->configure($field);

        self::assertSame('#ff5733', $fieldDto->getValue());
    }

    public function testFieldWithNullValue(): void
    {
        $field = ColorField::new('color');
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testShowSample(): void
    {
        $field = ColorField::new('color');
        $field->showSample();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ColorField::OPTION_SHOW_SAMPLE));
    }

    public function testHideSample(): void
    {
        $field = ColorField::new('color');
        $field->showSample(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ColorField::OPTION_SHOW_SAMPLE));
    }

    public function testShowValue(): void
    {
        $field = ColorField::new('color');
        $field->showValue();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ColorField::OPTION_SHOW_VALUE));
    }

    public function testHideValue(): void
    {
        $field = ColorField::new('color');
        $field->showValue(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ColorField::OPTION_SHOW_VALUE));
    }

    public function testShowBothSampleAndValue(): void
    {
        $field = ColorField::new('color');
        $field->showSample();
        $field->showValue();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ColorField::OPTION_SHOW_SAMPLE));
        self::assertTrue($fieldDto->getCustomOption(ColorField::OPTION_SHOW_VALUE));
    }

    public function testTemplateShowsSampleWhenEnabled(): void
    {
        $field = ColorField::new('color');
        $field->setValue('#ff5733');
        $field->showSample();
        $field->showValue(false);
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('class="color-sample"', $html);
        self::assertStringContainsString('style="background: #ff5733', $html);
        // the color value should only appear in attributes, not as text content after the span
        self::assertDoesNotMatchRegularExpression('/<\/span>\s*#ff5733/', $html);
    }

    public function testTemplateHidesSampleWhenDisabled(): void
    {
        $field = ColorField::new('color');
        $field->setValue('#ff5733');
        $field->showSample(false);
        $field->showValue();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringNotContainsString('class="color-sample"', $html);
        // the color value should appear as text content (not just in attributes)
        self::assertMatchesRegularExpression('/^\s*#ff5733\s*$/', $html);
    }

    public function testTemplateShowsValueWhenEnabled(): void
    {
        $field = ColorField::new('color');
        $field->setValue('#00ff00');
        $field->showSample(false);
        $field->showValue();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        // the color value should appear as text content (not just in attributes)
        self::assertMatchesRegularExpression('/^\s*#00ff00\s*$/', $html);
    }

    public function testTemplateShowsBothSampleAndValue(): void
    {
        $field = ColorField::new('color');
        $field->setValue('#0000ff');
        $field->showSample();
        $field->showValue();
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringContainsString('class="color-sample"', $html);
        self::assertStringContainsString('background: #0000ff', $html);
        // the value should appear as text content after the sample span
        self::assertMatchesRegularExpression('/<\/span>\s*#0000ff\s*$/', $html);
    }

    public function testTemplateShowsNothingWhenBothDisabled(): void
    {
        $field = ColorField::new('color');
        $field->setValue('#ffffff');
        $field->showSample(false);
        $field->showValue(false);
        $fieldDto = $this->configure($field);

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringNotContainsString('color-sample', $html);
        self::assertStringNotContainsString('#ffffff', $html);
    }
}
