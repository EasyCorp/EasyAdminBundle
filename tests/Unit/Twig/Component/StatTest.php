<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\Stat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

class StatTest extends TestCase
{
    private string $defaultLocale;

    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();
        \Locale::setDefault('en');
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
    }

    public function testMountDefaults(): void
    {
        $stat = $this->createStat();
        $stat->mount();

        $this->assertNull($stat->label);
        $this->assertNull($stat->value);
        $this->assertNull($stat->description);
        $this->assertNull($stat->delta);
        $this->assertTrue($stat->increaseIsGood);
        $this->assertNull($stat->getFormattedValue());
        $this->assertNull($stat->getFormattedDelta());
        $this->assertNull($stat->getDeltaDirection());
        $this->assertNull($stat->getDeltaCssClass());
    }

    public function testEmptyStringsAreTreatedAsNull(): void
    {
        $stat = $this->createStat();
        $stat->mount(label: '', value: '', description: '', delta: '');

        $this->assertNull($stat->label);
        $this->assertNull($stat->value);
        $this->assertNull($stat->description);
        $this->assertNull($stat->delta);
    }

    #[DataProvider('provideValues')]
    public function testFormattedValue(int|float|string|null $value, ?string $expectedValue): void
    {
        $stat = $this->createStat();
        $stat->mount(value: $value);

        $this->assertSame($expectedValue, $stat->getFormattedValue());
    }

    public static function provideValues(): iterable
    {
        yield 'integer' => [1234567, '1,234,567'];
        yield 'float' => [1234.5, '1,234.5'];
        yield 'zero' => [0, '0'];
        yield 'negative' => [-42, '-42'];
        yield 'string is displayed as it is' => ['$1,234 / month', '$1,234 / month'];
        yield 'numeric string is displayed as it is' => ['1234', '1234'];
        yield 'null' => [null, null];
    }

    public function testFormattedValueUsesTheCurrentLocale(): void
    {
        \Locale::setDefault('de');
        $stat = $this->createStat();
        $stat->mount(value: 1234.5);

        $this->assertSame('1.234,5', $stat->getFormattedValue());
    }

    public function testMarkupValuesAreReturnedUntouched(): void
    {
        $markup = new Markup('<strong>42</strong>', 'UTF-8');
        $stat = $this->createStat();
        $stat->mount(value: $markup);

        $this->assertSame($markup, $stat->getFormattedValue());
    }

    #[DataProvider('provideDeltas')]
    public function testDelta(int|float|string $delta, string $expectedFormattedDelta, string $expectedDirection): void
    {
        $stat = $this->createStat();
        $stat->mount(delta: $delta);

        $this->assertSame($expectedFormattedDelta, $stat->getFormattedDelta());
        $this->assertSame($expectedDirection, $stat->getDeltaDirection());
    }

    public static function provideDeltas(): iterable
    {
        yield 'positive float' => [8.2, '+8.2%', 'increase'];
        yield 'negative integer' => [-3, '-3%', 'decrease'];
        yield 'zero' => [0, '0%', 'no_change'];
        yield 'rounded to one decimal' => [12.345, '+12.3%', 'increase'];
        yield 'tiny negative change is no change' => [-0.04, '0%', 'no_change'];
        yield 'tiny positive change is no change' => [0.04, '0%', 'no_change'];
        yield 'large change' => [1250, '+1,250%', 'increase'];
        yield 'numeric string' => ['-7.5', '-7.5%', 'decrease'];
    }

    public function testFormattedDeltaUsesTheCurrentLocale(): void
    {
        \Locale::setDefault('es');
        $stat = $this->createStat();
        $stat->mount(delta: 8.2);

        $this->assertMatchesRegularExpression('/^\+8,2\s?%$/u', $stat->getFormattedDelta());
    }

    #[DataProvider('provideDeltaCssClasses')]
    public function testDeltaCssClass(int|float $delta, bool $increaseIsGood, string $expectedCssClass): void
    {
        $stat = $this->createStat();
        $stat->mount(delta: $delta, increaseIsGood: $increaseIsGood);

        $this->assertSame($expectedCssClass, $stat->getDeltaCssClass());
    }

    public static function provideDeltaCssClasses(): iterable
    {
        yield 'increase is good' => [5, true, 'ea-stat-delta-good'];
        yield 'decrease is bad' => [-5, true, 'ea-stat-delta-bad'];
        yield 'increase is bad' => [5, false, 'ea-stat-delta-bad'];
        yield 'decrease is good' => [-5, false, 'ea-stat-delta-good'];
        yield 'no change' => [0, true, 'ea-stat-delta-neutral'];
        yield 'no change when increase is bad' => [0, false, 'ea-stat-delta-neutral'];
    }

    public function testNonNumericDeltaThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "delta" prop of the Stat component must be a number');

        $this->createStat()->mount(delta: 'a lot');
    }

    public function testInfiniteDeltaThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "delta" prop of the Stat component must be a finite number.');

        $this->createStat()->mount(delta: \INF);
    }

    private function createStat(): Stat
    {
        return new Stat(new IntlFormatter());
    }
}
