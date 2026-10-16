<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Button;
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

    #[DataProvider('provideKnownStringVariants')]
    public function testMountWithKnownStringVariant(string $variantString, ButtonVariant $expectedVariant): void
    {
        $button = $this->createButton();
        $button->mount($variantString);

        $this->assertSame($expectedVariant, $button->variant);
    }

    public static function provideKnownStringVariants(): iterable
    {
        yield ['default', ButtonVariant::Default];
        yield ['primary', ButtonVariant::Primary];
        yield ['success', ButtonVariant::Success];
        yield ['warning', ButtonVariant::Warning];
        yield ['danger', ButtonVariant::Danger];
        yield ['info', ButtonVariant::Info];
    }

    public function testMountWithEnumVariant(): void
    {
        $button = $this->createButton();
        $button->mount(ButtonVariant::Danger);

        $this->assertSame(ButtonVariant::Danger, $button->variant);
    }

    #[DataProvider('provideEmptyVariants')]
    public function testMountWithEmptyVariantUsesDefault(?string $variant): void
    {
        $button = $this->createButton();
        $button->mount($variant);

        $this->assertSame(ButtonVariant::Default, $button->variant);
        $this->assertSame('btn btn-secondary', $button->getDefaultCssClass());
    }

    public static function provideEmptyVariants(): iterable
    {
        yield [null];
        yield [''];
    }

    public function testMountWithUnknownVariantIsRenderedAsCustomVariant(): void
    {
        $button = $this->createButton();
        $button->mount('custom');

        $this->assertNull($button->variant);
        $this->assertSame('btn btn-custom', $button->getDefaultCssClass());
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
    public function testGetDefaultCssClass(string|ButtonVariant|null $variant, string|Size $size, bool $isInvisible, bool $isBlock, bool $inactive, string $expected): void
    {
        $button = $this->createButton();
        $button->mount($variant, size: $size);
        $button->isInvisible = $isInvisible;
        $button->isBlock = $isBlock;
        $button->inactive = $inactive;

        $this->assertSame($expected, $button->getDefaultCssClass());
    }

    public static function provideCssClassCombinations(): iterable
    {
        yield 'default' => [null, 'md', false, false, false, 'btn btn-secondary'];
        yield 'primary' => [ButtonVariant::Primary, 'md', false, false, false, 'btn btn-primary'];
        yield 'small size' => ['danger', 'sm', false, false, false, 'btn btn-danger btn-sm'];
        yield 'large size' => ['info', 'lg', false, false, false, 'btn btn-info btn-lg'];
        yield 'large size as enum' => ['info', Size::Large, false, false, false, 'btn btn-info btn-lg'];
        yield 'invisible' => [null, 'md', true, false, false, 'btn btn-secondary btn-invisible'];
        yield 'block' => [null, 'md', false, true, false, 'btn btn-secondary btn-block'];
        yield 'inactive' => [null, 'md', false, false, true, 'btn btn-secondary disabled'];
        yield 'all combined' => ['warning', 'sm', true, true, true, 'btn btn-warning btn-sm btn-invisible btn-block disabled'];
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
