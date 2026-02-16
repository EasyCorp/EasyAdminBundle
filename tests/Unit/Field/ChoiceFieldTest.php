<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field;

use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\ChoiceConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Fixtures\ChoiceField\PriorityUnitEnum;
use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Field\Fixtures\ChoiceField\StatusBackedEnum;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use function Symfony\Component\Translation\t;

class ChoiceFieldTest extends AbstractFieldTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new ChoiceConfigurator();
    }

    public function testDefaultOptions(): void
    {
        $field = ChoiceField::new('foo');
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(ChoiceField::OPTION_CHOICES));
        self::assertFalse($fieldDto->getCustomOption(ChoiceField::OPTION_USE_TRANSLATABLE_CHOICES));
        self::assertFalse($fieldDto->getCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES));
        self::assertNull($fieldDto->getCustomOption(ChoiceField::OPTION_RENDER_AS_BADGES));
        self::assertFalse($fieldDto->getCustomOption(ChoiceField::OPTION_RENDER_EXPANDED));
        // default widget is set to autocomplete by configurator when expanded is false
        self::assertSame(ChoiceField::WIDGET_AUTOCOMPLETE, $fieldDto->getCustomOption(ChoiceField::OPTION_WIDGET));
        self::assertTrue($fieldDto->getCustomOption(ChoiceField::OPTION_ESCAPE_HTML_CONTENTS));
        self::assertSame(ChoiceType::class, $fieldDto->getFormType());
        self::assertStringContainsString('field-select', $fieldDto->getCssClass());
    }

    public function testFieldWithoutChoices(): void
    {
        $field = ChoiceField::new('foo');
        self::assertSame([], $this->configure($field)->getFormTypeOption(ChoiceField::OPTION_CHOICES));
    }

    public function testFieldWithEmptyChoices(): void
    {
        $field = ChoiceField::new('foo')->setChoices([]);
        self::assertSame([], $this->configure($field)->getFormTypeOption(ChoiceField::OPTION_CHOICES));
    }

    public function testFieldWithGroupedChoices(): void
    {
        $field = ChoiceField::new('foo')->setChoices([
            'a' => 1,
            'My group name' => [
                'b' => 2,
            ],
        ]);

        $field->setValue(1);
        self::assertSame('a', (string) $this->configure($field)->getFormattedValue());
        $field->setValue(2);
        self::assertSame('b', (string) $this->configure($field)->getFormattedValue());
    }

    public function testFieldWithUnitEnumChoices(): void
    {
        $field = ChoiceField::new('foo')->setChoices(PriorityUnitEnum::cases());

        $field->setValue(PriorityUnitEnum::High);
        self::assertSame(PriorityUnitEnum::High->name, (string) $this->configure($field)->getFormattedValue());
        $field->setValue(PriorityUnitEnum::Normal);
        self::assertSame(PriorityUnitEnum::Normal->name, (string) $this->configure($field)->getFormattedValue());
        $field->setValue(PriorityUnitEnum::Low);
        self::assertSame(PriorityUnitEnum::Low->name, (string) $this->configure($field)->getFormattedValue());
    }

    public function testFieldWithBackedEnumChoices(): void
    {
        $field = ChoiceField::new('foo')->setChoices(StatusBackedEnum::cases());

        $field->setValue(StatusBackedEnum::Draft);
        self::assertSame(StatusBackedEnum::Draft->name, (string) $this->configure($field)->getFormattedValue());
        $field->setValue(StatusBackedEnum::Published);
        self::assertSame(StatusBackedEnum::Published->name, (string) $this->configure($field)->getFormattedValue());
        $field->setValue(StatusBackedEnum::Deleted);
        self::assertSame(StatusBackedEnum::Deleted->name, (string) $this->configure($field)->getFormattedValue());
    }

    public function testFieldWithChoiceGeneratorCallback(): void
    {
        $choices = ['foo' => 1, 'bar' => 2];
        $field = ChoiceField::new('foo')->setChoices(static fn (): array => $choices);

        self::assertSame($choices, $this->configure($field)->getFormTypeOption(ChoiceField::OPTION_CHOICES));

        $field->setValue(1);
        self::assertSame('foo', (string) $this->configure($field)->getFormattedValue());
    }

    public function testFieldWithTranslatableChoices(): void
    {
        $field = ChoiceField::new('foo')->setTranslatableChoices([1 => t('foo'), 2 => 'bar']);

        $field->setValue(1);
        self::assertSame('foo', (string) $this->configure($field)->getFormattedValue());

        $field->setValue(2);
        self::assertSame('bar', (string) $this->configure($field)->getFormattedValue());
    }

    public function testFieldWithWrongVisualOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->renderExpanded();
        $field->renderAsNativeWidget(false);
        $this->configure($field);
    }

    public function testDefaultWidget(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);

        $field->renderExpanded(false);
        $field->setCustomOption(ChoiceField::OPTION_WIDGET, null);
        self::assertSame(ChoiceField::WIDGET_AUTOCOMPLETE, $this->configure($field)->getCustomOption(ChoiceField::OPTION_WIDGET));

        $field->renderExpanded(true);
        $field->setCustomOption(ChoiceField::OPTION_WIDGET, null);
        $fieldDto = $this->configure($field);
        self::assertSame(ChoiceField::WIDGET_NATIVE, $fieldDto->getCustomOption(ChoiceField::OPTION_WIDGET));
        self::assertSame('ea-autocomplete', $fieldDto->getFormTypeOption('attr.data-ea-widget'));
    }

    public function testFieldFormOptions(): void
    {
        $choices = ['a' => 1, 'b' => 2, 'c' => 3];
        $field = ChoiceField::new('foo')->setChoices($choices);
        $field->renderExpanded();
        $field->allowMultipleChoices();

        self::assertSame(
            [
                'choices' => $choices,
                'multiple' => true,
                'expanded' => true,
                'placeholder' => '',
                'attr' => ['data-ea-autocomplete-render-items-as-html' => 'false'],
            ],
            $this->configure($field)->getFormTypeOptions()
        );
    }

    public function testBadges(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);

        $field->setValue(1);
        self::assertSame('a', (string) $this->configure($field)->getFormattedValue());

        $field->setValue([1, 3]);
        self::assertSame('a, c', (string) $this->configure($field)->getFormattedValue());

        $field->setValue(1)->renderAsBadges();
        self::assertSame('<span class="badge badge-secondary">a</span>', (string) $this->configure($field)->getFormattedValue());

        $field->setValue([1, 3])->renderAsBadges();
        self::assertSame('<span class="badge badge-secondary">a</span><span class="badge badge-secondary">c</span>', (string) $this->configure($field)->getFormattedValue());

        $field->setValue(1)->renderAsBadges([1 => 'warning', '3' => 'danger']);
        self::assertSame('<span class="badge badge-warning">a</span>', (string) $this->configure($field)->getFormattedValue());

        $field->setValue([1, 3])->renderAsBadges([1 => 'warning', '3' => 'danger']);
        self::assertSame('<span class="badge badge-warning">a</span><span class="badge badge-danger">c</span>', (string) $this->configure($field)->getFormattedValue());

        $field->setValue(1)->renderAsBadges(static fn (mixed $value): string => $value > 1 ? 'success' : 'primary');
        self::assertSame('<span class="badge badge-primary">a</span>', (string) $this->configure($field)->getFormattedValue());

        $field->setValue([1, 3])->renderAsBadges(static fn (mixed $value): string => $value > 1 ? 'success' : 'primary');
        self::assertSame('<span class="badge badge-primary">a</span><span class="badge badge-success">c</span>', (string) $this->configure($field)->getFormattedValue());
    }

    public function testAllowMultipleChoicesEnabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->allowMultipleChoices();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES));
        self::assertTrue($fieldDto->getFormTypeOption('multiple'));
    }

    public function testAllowMultipleChoicesDisabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->allowMultipleChoices(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES));
        self::assertFalse($fieldDto->getFormTypeOption('multiple'));
    }

    public function testMultipleChoicesFormattedValue(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['Option A' => 1, 'Option B' => 2, 'Option C' => 3]);
        $field->allowMultipleChoices();
        $field->setValue([1, 2, 3]);
        $fieldDto = $this->configure($field);

        self::assertSame('Option A, Option B, Option C', (string) $fieldDto->getFormattedValue());
    }

    public function testRenderExpandedEnabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->renderExpanded();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ChoiceField::OPTION_RENDER_EXPANDED));
        self::assertTrue($fieldDto->getFormTypeOption('expanded'));
        self::assertSame(ChoiceField::WIDGET_NATIVE, $fieldDto->getCustomOption(ChoiceField::OPTION_WIDGET));
    }

    public function testRenderExpandedDisabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->renderExpanded(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ChoiceField::OPTION_RENDER_EXPANDED));
        self::assertFalse($fieldDto->getFormTypeOption('expanded'));
    }

    public function testRenderAsNativeWidgetEnabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->renderAsNativeWidget();
        $fieldDto = $this->configure($field);

        self::assertSame(ChoiceField::WIDGET_NATIVE, $fieldDto->getCustomOption(ChoiceField::OPTION_WIDGET));
    }

    public function testRenderAsNativeWidgetDisabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->renderAsNativeWidget(false);
        $fieldDto = $this->configure($field);

        self::assertSame(ChoiceField::WIDGET_AUTOCOMPLETE, $fieldDto->getCustomOption(ChoiceField::OPTION_WIDGET));
    }

    public function testRenderAsBadgesEnabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->setValue(1);
        $field->renderAsBadges();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ChoiceField::OPTION_RENDER_AS_BADGES));
        self::assertStringContainsString('badge', (string) $fieldDto->getFormattedValue());
    }

    public function testRenderAsBadgesDisabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->setValue(1);
        $field->renderAsBadges(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ChoiceField::OPTION_RENDER_AS_BADGES));
        self::assertStringNotContainsString('badge', (string) $fieldDto->getFormattedValue());
    }

    public function testRenderExpandedWithMultipleRendersAsCheckboxes(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->renderExpanded();
        $field->allowMultipleChoices();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getFormTypeOption('expanded'));
        self::assertTrue($fieldDto->getFormTypeOption('multiple'));
    }

    public function testRenderExpandedWithSingleRendersAsRadioButtons(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->renderExpanded();
        $field->allowMultipleChoices(false);
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getFormTypeOption('expanded'));
        self::assertFalse($fieldDto->getFormTypeOption('multiple'));
    }

    public function testAutocompleteAddsAttribute(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->autocomplete();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ChoiceField::OPTION_AUTOCOMPLETE));
    }

    public function testEscapeHtmlContentsEnabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['<b>Bold</b>' => 1, '<i>Italic</i>' => 2]);
        $field->escapeHtml();
        $fieldDto = $this->configure($field);

        self::assertTrue($fieldDto->getCustomOption(ChoiceField::OPTION_ESCAPE_HTML_CONTENTS));
        self::assertSame('false', $fieldDto->getFormTypeOption('attr.data-ea-autocomplete-render-items-as-html'));
    }

    public function testEscapeHtmlContentsDisabled(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['<b>Bold</b>' => 1, '<i>Italic</i>' => 2]);
        $field->escapeHtml(false);
        $fieldDto = $this->configure($field);

        self::assertFalse($fieldDto->getCustomOption(ChoiceField::OPTION_ESCAPE_HTML_CONTENTS));
        self::assertSame('true', $fieldDto->getFormTypeOption('attr.data-ea-autocomplete-render-items-as-html'));
    }

    public function testFieldWithNullValue(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->setValue(null);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getValue());
    }

    public function testFieldWithValueNotInChoices(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2, 'c' => 3]);
        $field->setValue(999);
        $fieldDto = $this->configure($field);

        self::assertSame('', (string) $fieldDto->getFormattedValue());
    }

    public function testPreferredChoicesWithArray(): void
    {
        $choices = ['a' => 1, 'b' => 2, 'c' => 3];
        $field = ChoiceField::new('foo')->setChoices($choices)->setPreferredChoices([1, 2]);
        $fieldDto = $this->configure($field);

        self::assertSame([1, 2], $fieldDto->getFormTypeOption('preferred_choices'));
    }

    public function testPreferredChoicesWithCallable(): void
    {
        $choices = ['a' => 1, 'b' => 2, 'c' => 3];
        $callable = static fn ($value): bool => $value < 3;
        $field = ChoiceField::new('foo')->setChoices($choices)->setPreferredChoices($callable);
        $fieldDto = $this->configure($field);

        self::assertSame($callable, $fieldDto->getFormTypeOption('preferred_choices'));
    }

    public function testPreferredChoicesDefaultValue(): void
    {
        $field = ChoiceField::new('foo')->setChoices(['a' => 1, 'b' => 2]);
        $fieldDto = $this->configure($field);

        self::assertNull($fieldDto->getCustomOption(ChoiceField::OPTION_PREFERRED_CHOICES));
        self::assertNull($fieldDto->getFormTypeOption('preferred_choices'));
    }
}
