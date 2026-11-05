<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Button;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonColor;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonElement;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonType;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\ButtonVariant;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class ButtonTest extends TestCase
{
    private function createButton(): Button
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn (string $id): string => 'translated: '.$id);

        return new Button($translator);
    }

    #[DataProvider('provideColors')]
    public function testMountResolvesColor(string|ButtonColor|null $color, ButtonColor $expectedColor): void
    {
        $button = $this->createButton();
        $button->mount(color: $color);

        $this->assertSame($expectedColor, $button->color);
    }

    public static function provideColors(): iterable
    {
        yield [null, ButtonColor::Default];
        yield ['', ButtonColor::Default];
        yield ['default', ButtonColor::Default];
        yield ['secondary', ButtonColor::Default];
        yield ['primary', ButtonColor::Primary];
        yield ['danger', ButtonColor::Danger];
        yield [ButtonColor::Info, ButtonColor::Info];
    }

    public function testMountWithUnknownColorIsRenderedAsCustomColor(): void
    {
        $button = $this->createButton();
        $button->mount(color: 'custom');

        $this->assertNull($button->color);
        $this->assertSame('btn btn-custom btn-outline', $button->getDefaultCssClass());
    }

    #[DataProvider('provideVariants')]
    public function testMountResolvesVariant(string|ButtonVariant $variant, ButtonVariant $expectedVariant): void
    {
        $button = $this->createButton();
        $button->mount(variant: $variant);

        $this->assertSame($expectedVariant, $button->variant);
    }

    public static function provideVariants(): iterable
    {
        yield ['solid', ButtonVariant::Solid];
        yield ['outline', ButtonVariant::Outline];
        yield ['light', ButtonVariant::Light];
        yield ['ghost', ButtonVariant::Ghost];
        yield ['link', ButtonVariant::Link];
        yield [ButtonVariant::Ghost, ButtonVariant::Ghost];
    }

    #[DataProvider('provideDefaultVariantsOfColors')]
    public function testEachColorHasADefaultVariant(ButtonColor $color, ButtonVariant $expectedVariant): void
    {
        $button = $this->createButton();
        $button->mount(color: $color);

        $this->assertNull($button->variant);
        $this->assertSame($expectedVariant, $button->getResolvedVariant());
    }

    public static function provideDefaultVariantsOfColors(): iterable
    {
        yield [ButtonColor::Default, ButtonVariant::Outline];
        yield [ButtonColor::Primary, ButtonVariant::Solid];
        yield [ButtonColor::Success, ButtonVariant::Light];
        yield [ButtonColor::Warning, ButtonVariant::Light];
        yield [ButtonColor::Danger, ButtonVariant::Light];
        yield [ButtonColor::Info, ButtonVariant::Light];
    }

    public function testMountWithUnknownVariantIsRenderedAsCustomVariant(): void
    {
        $button = $this->createButton();
        $button->mount(variant: 'outline-primary');

        $this->assertNull($button->variant);
        $this->assertSame('btn btn-secondary btn-outline-primary', $button->getDefaultCssClass());
    }

    #[IgnoreDeprecations]
    #[DataProvider('provideLegacyColorVariants')]
    public function testPassingAColorInTheVariantPropIsDeprecated(string|ButtonVariant $variant, ButtonColor $expectedColor): void
    {
        $legacyColor = $variant instanceof ButtonVariant ? $variant->value : $variant;
        $this->expectUserDeprecationMessage(sprintf('Since easycorp/easyadmin-bundle 5.9.0: Passing a color ("%1$s") in the "variant" prop of the <twig:ea:Button> component is deprecated. Use the "color" prop instead (e.g. color="%1$s"); the "variant" prop now defines the visual style of the button ("solid", "outline", "light", "ghost" or "link").', $legacyColor));

        $button = $this->createButton();
        $button->mount(variant: $variant);

        $this->assertSame($expectedColor, $button->color);
        $this->assertNull($button->variant);
        $this->assertSame($expectedColor->getDefaultVariant(), $button->getResolvedVariant());
    }

    public static function provideLegacyColorVariants(): iterable
    {
        yield ['default', ButtonColor::Default];
        yield ['secondary', ButtonColor::Default];
        yield ['primary', ButtonColor::Primary];
        yield ['danger', ButtonColor::Danger];
        yield [ButtonVariant::Success, ButtonColor::Success];
    }

    #[IgnoreDeprecations]
    public function testAColorInTheVariantPropDoesNotOverrideTheColorProp(): void
    {
        $button = $this->createButton();
        $button->mount(color: 'primary', variant: 'danger');

        $this->assertSame(ButtonColor::Primary, $button->color);
        $this->assertSame('btn btn-primary btn-solid', $button->getDefaultCssClass());
    }

    #[IgnoreDeprecations]
    public function testTheIsInvisiblePropIsDeprecated(): void
    {
        $button = $this->createButton();

        $this->expectUserDeprecationMessage('Since easycorp/easyadmin-bundle 5.9.0: The "isInvisible" prop of the <twig:ea:Button> component is deprecated, use variant="ghost" instead.');

        $button->migrateHtmlAttributes(['isInvisible' => true]);
    }

    public function testAFalseIsInvisiblePropDoesNotTriggerTheDeprecation(): void
    {
        $button = $this->createButton();

        $this->assertSame(['isInvisible' => ''], $button->migrateHtmlAttributes(['isInvisible' => '']));
    }

    #[DataProvider('provideHtmlElements')]
    public function testMountHtmlElement(string|ButtonElement|null $htmlElement, ButtonElement $expected): void
    {
        $button = $this->createButton();
        $button->mount(htmlElement: $htmlElement);

        $this->assertSame($expected, $button->htmlElement);
    }

    public static function provideHtmlElements(): iterable
    {
        yield ['a', ButtonElement::A];
        yield ['form', ButtonElement::Form];
        yield ['button', ButtonElement::Button];
        yield [ButtonElement::Form, ButtonElement::Form];
        yield [null, ButtonElement::Button];
        yield ['', ButtonElement::Button];
        yield ['unknown', ButtonElement::Button];
    }

    #[DataProvider('provideTypes')]
    public function testMountType(string|ButtonType|null $type, ButtonType $expected): void
    {
        $button = $this->createButton();
        $button->mount(type: $type);

        $this->assertSame($expected, $button->type);
    }

    public static function provideTypes(): iterable
    {
        yield ['button', ButtonType::Button];
        yield ['submit', ButtonType::Submit];
        yield [ButtonType::Button, ButtonType::Button];
        yield [null, ButtonType::Submit];
        yield ['', ButtonType::Submit];
        yield ['unknown', ButtonType::Submit];
    }

    #[DataProvider('provideMethods')]
    public function testMountMethod(?string $method, string $expectedMethod, bool $expectedOverride, string $expectedFormMethod): void
    {
        $button = $this->createButton();
        $button->mount(method: $method);

        $this->assertSame($expectedMethod, $button->method);
        $this->assertSame($expectedOverride, $button->usesMethodOverride());
        $this->assertSame($expectedFormMethod, $button->getFormMethod());
    }

    public static function provideMethods(): iterable
    {
        yield [null, 'POST', false, 'POST'];
        yield ['', 'POST', false, 'POST'];
        yield ['get', 'GET', false, 'GET'];
        yield ['POST', 'POST', false, 'POST'];
        yield ['delete', 'DELETE', true, 'POST'];
        yield ['PATCH', 'PATCH', true, 'POST'];
    }

    #[DataProvider('provideSizeValues')]
    public function testMountResolvesSize(string|Size $size, Size $expectedSize): void
    {
        $button = $this->createButton();
        $button->mount(size: $size);

        $this->assertSame($expectedSize, $button->size);
    }

    public static function provideSizeValues(): iterable
    {
        yield 'string' => ['sm', Size::Small];
        yield 'enum' => [Size::Small, Size::Small];
        yield 'unknown string falls back to md' => ['nope', Size::Medium];
    }

    #[DataProvider('provideCssClassCombinations')]
    public function testGetDefaultCssClass(string|ButtonColor|null $color, ?string $variant, string|Size $size, bool $isBlock, bool $inactive, string $expected): void
    {
        $button = $this->createButton();
        $button->mount(color: $color, variant: $variant, size: $size);
        $button->isBlock = $isBlock;
        $button->inactive = $inactive;

        $this->assertSame($expected, $button->getDefaultCssClass());
    }

    public static function provideCssClassCombinations(): iterable
    {
        yield 'default' => [null, null, 'md', false, false, 'btn btn-secondary btn-outline'];
        yield 'primary' => [ButtonColor::Primary, null, 'md', false, false, 'btn btn-primary btn-solid'];
        yield 'small size' => ['danger', null, 'sm', false, false, 'btn btn-danger btn-light btn-sm'];
        yield 'large size' => ['info', null, 'lg', false, false, 'btn btn-info btn-light btn-lg'];
        yield 'large size as enum' => ['info', null, Size::Large, false, false, 'btn btn-info btn-light btn-lg'];
        yield 'ghost' => [null, 'ghost', 'md', false, false, 'btn btn-secondary btn-ghost'];
        yield 'solid danger' => ['danger', 'solid', 'md', false, false, 'btn btn-danger btn-solid'];
        yield 'block' => [null, null, 'md', true, false, 'btn btn-secondary btn-outline btn-block'];
        yield 'inactive' => [null, null, 'md', false, true, 'btn btn-secondary btn-outline disabled'];
        yield 'all combined' => ['warning', 'ghost', 'sm', true, true, 'btn btn-warning btn-ghost btn-sm btn-block disabled'];
    }

    public function testTheDeprecatedIsInvisiblePropRendersAGhostButton(): void
    {
        $button = $this->createButton();
        $button->mount(color: 'danger');
        $button->isInvisible = true;

        $this->assertSame('btn btn-danger btn-ghost', $button->getDefaultCssClass());
    }

    #[IgnoreDeprecations]
    public function testDeprecatedHtmlAttributesPropIsMigratedToRegularAttributes(): void
    {
        $button = $this->createButton();

        $this->expectUserDeprecationMessage('Since easycorp/easyadmin-bundle 5.2.0: The "htmlAttributes" prop of the <twig:ea:Button> component is deprecated, pass the attributes directly on the component (or use the "{{ ... }}" spread syntax for dynamic maps) instead.');

        $data = $button->migrateHtmlAttributes([
            'variant' => 'primary',
            'data-foo' => 'direct',
            'htmlAttributes' => [
                'data-foo' => 'from-map',
                'data-bar' => 'bar',
                'data-null' => null,
                'title' => new TranslatableMessage('some.title'),
            ],
        ]);

        $this->assertArrayNotHasKey('htmlAttributes', $data);
        // attributes passed directly on the component win over the map ones
        $this->assertSame('direct', $data['data-foo']);
        $this->assertSame('bar', $data['data-bar']);
        // the old template rendered null values as attr=""
        $this->assertSame('', $data['data-null']);
        // the old template translated TranslatableInterface values
        $this->assertSame('translated: some.title', $data['title']);
        $this->assertSame('primary', $data['variant']);
    }

    #[IgnoreDeprecations]
    public function testDeprecatedHtmlAttributesPropIgnoresNonIterableValues(): void
    {
        $button = $this->createButton();

        $this->expectUserDeprecationMessage('Since easycorp/easyadmin-bundle 5.2.0: The "htmlAttributes" prop of the <twig:ea:Button> component is deprecated, pass the attributes directly on the component (or use the "{{ ... }}" spread syntax for dynamic maps) instead.');

        // the old template silently ignored non-iterable values (e.g. a string
        // literal passed without the '{{ }}' delimiters)
        $data = $button->migrateHtmlAttributes(['htmlAttributes' => "{class: 'btn-block'}"]);

        $this->assertSame([], $data);
    }

    public function testMountWithoutHtmlAttributesDoesNotTriggerDeprecation(): void
    {
        $button = $this->createButton();

        $this->assertSame(['variant' => 'primary'], $button->migrateHtmlAttributes(['variant' => 'primary']));
    }
}
