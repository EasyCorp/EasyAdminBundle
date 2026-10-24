<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ComponentDefaultsDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider;
use EasyCorp\Bundle\EasyAdminBundle\Provider\ComponentDefaultsProvider;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Badge;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeColor;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\BadgeVariant;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Radius;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\Size;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BadgeTest extends TestCase
{
    #[DataProvider('provideKnownStringColors')]
    public function testMountWithKnownStringColor(string $color, BadgeColor $expectedColor): void
    {
        $badge = $this->createBadge();
        $badge->mount($color);

        $this->assertSame($expectedColor, $badge->color);
    }

    public static function provideKnownStringColors(): iterable
    {
        yield ['default', BadgeColor::Default];
        yield ['primary', BadgeColor::Primary];
        yield ['secondary', BadgeColor::Secondary];
        yield ['success', BadgeColor::Success];
        yield ['danger', BadgeColor::Danger];
        yield ['warning', BadgeColor::Warning];
        yield ['info', BadgeColor::Info];
        yield ['light', BadgeColor::Light];
        yield ['dark', BadgeColor::Dark];
    }

    public function testMountWithEnumColor(): void
    {
        $badge = $this->createBadge();
        $badge->mount(BadgeColor::Warning);

        $this->assertSame(BadgeColor::Warning, $badge->color);
    }

    public function testMountDefaults(): void
    {
        $badge = $this->createBadge();
        $badge->mount();

        $this->assertSame(BadgeColor::Default, $badge->color);
        $this->assertSame(BadgeVariant::Light, $badge->variant);
        $this->assertSame('badge badge-secondary', $badge->getDefaultCssClass());
    }

    #[DataProvider('provideEmptyColors')]
    public function testMountWithEmptyColorRendersNoColorClass(?string $color): void
    {
        $badge = $this->createBadge();
        $badge->mount($color);

        $this->assertNull($badge->color);
        $this->assertSame('badge', $badge->getDefaultCssClass());
    }

    public static function provideEmptyColors(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
    }

    public function testMountWithUnknownStringColorIsRenderedAsCustomColor(): void
    {
        $badge = $this->createBadge();
        $badge->mount('currency');

        // unknown colors don't match the BadgeColor enum...
        $this->assertNull($badge->color);
        // ...but they are passed through as a custom 'badge-{color}' CSS class
        $this->assertSame('badge badge-currency', $badge->getDefaultCssClass());
    }

    #[DataProvider('provideVariants')]
    public function testMountResolvesVariant(string|BadgeVariant $variant, BadgeVariant $expectedVariant): void
    {
        $badge = $this->createBadge();
        $badge->mount(variant: $variant);

        $this->assertSame($expectedVariant, $badge->variant);
        $this->assertSame(BadgeColor::Default, $badge->color);
    }

    public static function provideVariants(): iterable
    {
        yield 'light' => ['light', BadgeVariant::Light];
        yield 'solid' => ['solid', BadgeVariant::Solid];
        yield 'outline' => ['outline', BadgeVariant::Outline];
        yield 'dot' => ['dot', BadgeVariant::Dot];
        yield 'enum' => [BadgeVariant::Solid, BadgeVariant::Solid];
    }

    public function testMountUsesTheBadgeVariantOfTheCrudController(): void
    {
        $crudDto = new CrudDto();
        $crudDto->setComponentDefaults(new ComponentDefaultsDto(BadgeVariant::Dot));

        $badge = $this->createBadge($crudDto);
        $badge->mount('success');

        $this->assertSame(BadgeVariant::Dot, $badge->variant);
        $this->assertTrue($badge->hasDot());
        $this->assertSame('badge badge-success badge-variant-dot', $badge->getDefaultCssClass());
    }

    public function testExplicitVariantOverridesTheBadgeVariantOfTheCrudController(): void
    {
        $crudDto = new CrudDto();
        $crudDto->setComponentDefaults(new ComponentDefaultsDto(BadgeVariant::Dot));

        $badge = $this->createBadge($crudDto);
        $badge->mount('success', 'solid');

        $this->assertSame(BadgeVariant::Solid, $badge->variant);
        $this->assertFalse($badge->hasDot());
    }

    public function testMountWithoutAdminContextUsesTheLightVariant(): void
    {
        $badge = new Badge(new ComponentDefaultsProvider(new AdminContextProvider(new RequestStack())));
        $badge->mount('success');

        $this->assertSame(BadgeVariant::Light, $badge->variant);
    }

    #[IgnoreDeprecations]
    #[DataProvider('provideLegacyColorVariants')]
    public function testMountWithColorInVariantIsDeprecated(string|BadgeVariant $variant, ?BadgeColor $expectedColor, string $expectedCssClass): void
    {
        $legacyColor = $variant instanceof BadgeVariant ? $variant->value : $variant;
        $this->expectUserDeprecationMessage(sprintf('Since easycorp/easyadmin-bundle 5.7.0: Passing a color ("%s") in the "variant" prop of the <twig:ea:Badge> component is deprecated. Use the "color" prop instead (e.g. color="%s"); the "variant" prop now defines the visual style of the badge ("light", "solid", "outline" or "dot").', $legacyColor, $legacyColor));

        $badge = $this->createBadge();
        $badge->mount(variant: $variant);

        $this->assertSame($expectedColor, $badge->color);
        $this->assertSame(BadgeVariant::Light, $badge->variant);
        $this->assertSame($expectedCssClass, $badge->getDefaultCssClass());
    }

    public static function provideLegacyColorVariants(): iterable
    {
        yield 'color name' => ['success', BadgeColor::Success, 'badge badge-success'];
        yield 'deprecated enum case' => [BadgeVariant::Danger, BadgeColor::Danger, 'badge badge-danger'];
        yield 'custom color' => ['currency', null, 'badge badge-currency'];
    }

    #[IgnoreDeprecations]
    public function testExplicitColorWinsOverTheColorPassedInVariant(): void
    {
        $badge = $this->createBadge();
        $badge->mount('danger', 'success');

        $this->assertSame(BadgeColor::Danger, $badge->color);
    }

    #[DataProvider('provideRadiusValues')]
    public function testMountResolvesRadius(string|Radius|null $radius, ?Radius $expectedRadius): void
    {
        $badge = $this->createBadge();
        $badge->mount(radius: $radius);

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
        $badge = $this->createBadge();
        $badge->mount(size: $size);

        $this->assertSame($expectedSize, $badge->size);
    }

    public static function provideSizeValues(): iterable
    {
        yield 'string' => ['sm', Size::Small];
        yield 'enum' => [Size::Large, Size::Large];
        yield 'unknown string falls back to md' => ['nope', Size::Medium];
    }

    #[DataProvider('provideGetDefaultCssClassData')]
    public function testGetDefaultCssClass(string|BadgeColor|null $color, ?string $variant, string|Size $size, string|Radius|null $radius, string $expectedCssClass): void
    {
        $badge = $this->createBadge();
        $badge->mount($color, $variant, $radius, $size);

        $this->assertSame($expectedCssClass, $badge->getDefaultCssClass());
    }

    public static function provideGetDefaultCssClassData(): iterable
    {
        yield 'known color' => ['success', null, 'md', null, 'badge badge-success'];
        yield 'enum color' => [BadgeColor::Info, null, 'md', null, 'badge badge-info'];
        yield 'default color' => [BadgeColor::Default, null, 'md', null, 'badge badge-secondary'];
        yield 'custom color' => ['currency', null, 'md', null, 'badge badge-currency'];
        yield 'solid variant' => ['danger', 'solid', 'md', null, 'badge badge-danger badge-variant-solid'];
        yield 'outline variant' => ['primary', 'outline', 'md', null, 'badge badge-primary badge-variant-outline'];
        yield 'small size' => ['secondary', null, 'sm', null, 'badge badge-secondary badge-sm'];
        yield 'large size as enum' => ['secondary', null, Size::Large, null, 'badge badge-secondary badge-lg'];
        yield 'radius full' => ['secondary', null, 'md', 'full', 'badge badge-secondary ea-rounded-full'];
        yield 'small size and radius' => ['warning', 'dot', 'sm', Radius::Large, 'badge badge-warning badge-variant-dot badge-sm ea-rounded-lg'];
        yield 'no color' => ['', null, 'md', null, 'badge'];
    }

    private function createBadge(?CrudDto $crudDto = null): Badge
    {
        $adminContext = AdminContext::forTesting(crudContext: CrudContext::forTesting($crudDto ?? new CrudDto()));

        $requestStack = new RequestStack();
        $requestStack->push(new Request(attributes: [EA::CONTEXT_REQUEST_ATTRIBUTE => $adminContext]));

        return new Badge(new ComponentDefaultsProvider(new AdminContextProvider($requestStack)));
    }
}
