<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Alert;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Option\AlertVariant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AlertTest extends TestCase
{
    #[DataProvider('provideKnownStringVariants')]
    public function testMountWithKnownStringVariant(string $variantString, AlertVariant $expectedVariant): void
    {
        $alert = new Alert();
        $alert->mount($variantString);

        $this->assertSame($expectedVariant, $alert->variant);
    }

    public static function provideKnownStringVariants(): iterable
    {
        yield ['success', AlertVariant::Success];
        yield ['danger', AlertVariant::Danger];
        yield ['warning', AlertVariant::Warning];
        yield ['info', AlertVariant::Info];
        yield ['error', AlertVariant::Error];
        yield ['notice', AlertVariant::Notice];
    }

    public function testMountWithEnumVariant(): void
    {
        $alert = new Alert();
        $alert->mount(AlertVariant::Warning);

        $this->assertSame(AlertVariant::Warning, $alert->variant);
    }

    public function testMountWithUnknownStringVariantFallsBackToInfo(): void
    {
        $alert = new Alert();
        $alert->mount('attr_conf_activated');

        $this->assertSame(AlertVariant::Info, $alert->variant);
    }

    public function testMountWithoutVariantFallsBackToInfo(): void
    {
        $alert = new Alert();
        $alert->mount();

        $this->assertSame(AlertVariant::Info, $alert->variant);
    }

    public function testIconAndTitleAreNullByDefault(): void
    {
        $alert = new Alert();

        $this->assertNull($alert->icon);
        $this->assertNull($alert->title);
    }

    #[DataProvider('provideAllVariants')]
    public function testMountDoesNotSetDefaultIconForVariants(AlertVariant $variant): void
    {
        $alert = new Alert();
        $alert->mount($variant);

        $this->assertNull($alert->icon);
    }

    public static function provideAllVariants(): iterable
    {
        foreach (AlertVariant::cases() as $variant) {
            yield $variant->value => [$variant];
        }
    }

    public function testIconAndTitleDoNotChangeDefaultCssClass(): void
    {
        $alert = new Alert();
        $alert->mount('success');
        $alert->icon = 'internal:circle-check';
        $alert->title = 'Some title';

        $this->assertSame('alert alert-success', $alert->getDefaultCssClass());
    }

    #[DataProvider('provideGetDefaultCssClassData')]
    public function testGetDefaultCssClass(string|AlertVariant $variant, bool $withDismissButton, string $expectedCssClass): void
    {
        $alert = new Alert();
        $alert->mount($variant);
        $alert->withDismissButton = $withDismissButton;

        $this->assertSame($expectedCssClass, $alert->getDefaultCssClass());
    }

    public static function provideGetDefaultCssClassData(): iterable
    {
        yield 'known variant' => ['success', false, 'alert alert-success'];
        yield 'error maps to danger' => ['error', false, 'alert alert-danger'];
        yield 'notice maps to info' => ['notice', false, 'alert alert-info'];
        yield 'enum variant' => [AlertVariant::Warning, false, 'alert alert-warning'];
        yield 'with dismiss button' => ['success', true, 'alert alert-success alert-dismissible'];
        yield 'unknown variant adds custom class' => ['attr_conf_activated', false, 'alert alert-info alert-attr_conf_activated'];
        yield 'unknown variant with dismiss button' => ['attr_conf_activated', true, 'alert alert-info alert-attr_conf_activated alert-dismissible'];
    }
}
