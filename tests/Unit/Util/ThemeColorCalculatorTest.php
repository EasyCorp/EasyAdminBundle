<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Util;

use EasyCorp\Bundle\EasyAdminBundle\Util\ThemeColorCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ThemeColorCalculatorTest extends TestCase
{
    #[DataProvider('provideDarkBackgrounds')]
    public function testDarkBackgroundsGetWhiteForeground(string $color): void
    {
        $this->assertSame('#fff', ThemeColorCalculator::foregroundFor($color));
    }

    public static function provideDarkBackgrounds(): iterable
    {
        yield ['#15803d']; // green-700
        yield ['#1e3a8a']; // blue-900
        yield ['#000'];
        yield ['rgb(21, 128, 61)'];
        yield ['hsl(230, 61%, 58%)']; // the default EasyAdmin primary
        yield ['oklch(0.45 0.15 260)'];
    }

    #[DataProvider('provideLightBackgrounds')]
    public function testLightBackgroundsGetBlackForeground(string $color): void
    {
        $this->assertSame('#000', ThemeColorCalculator::foregroundFor($color));
    }

    public static function provideLightBackgrounds(): iterable
    {
        yield ['#facc15']; // yellow-400
        yield ['#fff'];
        yield ['rgb(250 204 21)'];
        yield ['hsl(48 96% 53%)'];
        yield ['oklch(0.9 0.15 100)'];
    }

    public function testRelativeLuminanceBounds(): void
    {
        $this->assertEqualsWithDelta(0.0, ThemeColorCalculator::relativeLuminance('#000'), 0.001);
        $this->assertEqualsWithDelta(1.0, ThemeColorCalculator::relativeLuminance('#fff'), 0.001);
        $this->assertEqualsWithDelta(1.0, ThemeColorCalculator::relativeLuminance('oklch(1 0 0)'), 0.01);
    }

    public function testHslMatchesItsHexEquivalent(): void
    {
        $this->assertEqualsWithDelta(
            ThemeColorCalculator::relativeLuminance('#15803d'),
            ThemeColorCalculator::relativeLuminance('hsl(146, 72%, 29%)'),
            0.01
        );
    }
}
