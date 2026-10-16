<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Badge;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Radius;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BadgeTest extends TestCase
{
    #[DataProvider('provideKnownStringVariants')]
    public function testMountWithKnownStringVariant(string $variantString, BadgeVariant $expectedVariant): void
    {
        $badge = new Badge();
        $badge->mount($variantString);

        $this->assertSame($expectedVariant, $badge->variant);
    }

    public static function provideKnownStringVariants(): iterable
    {
        yield ['primary', BadgeVariant::Primary];
        yield ['secondary', BadgeVariant::Secondary];
        yield ['success', BadgeVariant::Success];
        yield ['danger', BadgeVariant::Danger];
        yield ['warning', BadgeVariant::Warning];
        yield ['info', BadgeVariant::Info];
        yield ['light', BadgeVariant::Light];
        yield ['dark', BadgeVariant::Dark];
        yield ['outline', BadgeVariant::Outline];
    }

    public function testMountWithEnumVariant(): void
    {
        $badge = new Badge();
        $badge->mount(BadgeVariant::Warning);

        $this->assertSame(BadgeVariant::Warning, $badge->variant);
    }

    public function testMountDefaultsToSecondaryVariant(): void
    {
        $badge = new Badge();
        $badge->mount();

        $this->assertSame(BadgeVariant::Secondary, $badge->variant);
    }

    #[DataProvider('provideEmptyVariants')]
    public function testMountWithEmptyVariantRendersNoVariantClass(?string $variant): void
    {
        $badge = new Badge();
        $badge->mount($variant);

        // an explicit empty/null variant renders a badge with no variant CSS class
        $this->assertNull($badge->variant);
        $this->assertSame('badge', $badge->getDefaultCssClass());
    }

    public static function provideEmptyVariants(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }

    public function testMountWithUnknownStringVariantIsRenderedAsCustomVariant(): void
    {
        $badge = new Badge();
        $badge->mount('currency');

        // unknown variants don't match the BadgeVariant enum...
        $this->assertNull($badge->variant);
        // ...but they are passed through as a custom 'badge-{variant}' CSS class
        $this->assertSame('badge badge-currency', $badge->getDefaultCssClass());
    }

    #[DataProvider('provideRadiusValues')]
    public function testMountResolvesRadius(string|Radius|null $radius, ?Radius $expectedRadius): void
    {
        $badge = new Badge();
        $badge->mount('secondary', $radius);

        $this->assertSame($expectedRadius, $badge->radius);
    }

    public static function provideRadiusValues(): iterable
    {
        yield 'null' => [null, null];
        yield 'string' => ['full', Radius::Full];
        yield 'enum' => [Radius::Small, Radius::Small];
        yield 'unknown string falls back to null' => ['nope', null];
    }

    #[DataProvider('provideSizeValues')]
    public function testMountResolvesSize(string|Size $size, Size $expectedSize): void
    {
        $badge = new Badge();
        $badge->mount('secondary', null, $size);

        $this->assertSame($expectedSize, $badge->size);
    }

    public static function provideSizeValues(): iterable
    {
        yield 'string' => ['sm', Size::Small];
        yield 'enum' => [Size::Small, Size::Small];
        yield 'unknown string falls back to md' => ['nope', Size::Medium];
    }

    #[DataProvider('provideGetDefaultCssClassData')]
    public function testGetDefaultCssClass(string|BadgeVariant|null $variant, string|Size $size, string|Radius|null $radius, string $expectedCssClass): void
    {
        $badge = new Badge();
        $badge->mount($variant, $radius, $size);

        $this->assertSame($expectedCssClass, $badge->getDefaultCssClass());
    }

    public static function provideGetDefaultCssClassData(): iterable
    {
        yield 'known variant' => ['success', 'md', null, 'badge badge-success'];
        yield 'enum variant' => [BadgeVariant::Info, 'md', null, 'badge badge-info'];
        yield 'custom variant' => ['currency', 'md', null, 'badge badge-currency'];
        yield 'small size' => ['secondary', 'sm', null, 'badge badge-secondary badge-sm'];
        yield 'small size as enum' => ['secondary', Size::Small, null, 'badge badge-secondary badge-sm'];
        yield 'radius full' => ['secondary', 'md', 'full', 'badge badge-secondary ea-rounded-full'];
        yield 'small size and radius' => ['warning', 'sm', Radius::Large, 'badge badge-warning badge-sm ea-rounded-lg'];
        yield 'no variant' => ['', 'md', null, 'badge'];
    }
}
