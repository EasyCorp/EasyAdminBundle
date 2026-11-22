<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use EasyCorp\Bundle\EasyAdminBundle\Twig\Component\BarChart;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BarChartTest extends TestCase
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

    public function testEmptyData(): void
    {
        $chart = $this->createChart([]);

        $this->assertTrue($chart->isEmpty());
        $this->assertSame([], $chart->getBars());
    }

    public function testPositiveValues(): void
    {
        $chart = $this->createChart(['Jan' => 50, 'Feb' => 100, 'Mar' => 25.5]);

        $this->assertFalse($chart->isEmpty());
        $this->assertSame(100.0, $chart->getBaselineY());
        $this->assertFalse($chart->hasNegativeValues());
        $this->assertSame('100', $chart->getFormattedMaxValue());
        $this->assertSame('0', $chart->getFormattedMinValue());

        $bars = $chart->getBars();
        $this->assertCount(3, $bars);
        $this->assertSame(['label' => 'Jan', 'value' => 50, 'formattedValue' => '50', 'x' => 3.33, 'y' => 50.0, 'width' => 26.67, 'height' => 50.0, 'negative' => false], $bars[0]);
        $this->assertSame([36.67, 0.0, 100.0], [$bars[1]['x'], $bars[1]['y'], $bars[1]['height']]);
        $this->assertSame([70.0, 74.5, 25.5, '25.5'], [$bars[2]['x'], $bars[2]['y'], $bars[2]['height'], $bars[2]['formattedValue']]);
    }

    public function testNegativeValuesUseAZeroBaseline(): void
    {
        $chart = $this->createChart(['Profit' => 75, 'Loss' => -25]);

        $this->assertTrue($chart->hasNegativeValues());
        $this->assertSame(75.0, $chart->getBaselineY());
        $this->assertSame('-25', $chart->getFormattedMinValue());

        [$profit, $loss] = $chart->getBars();
        $this->assertSame([0.0, 75.0, false], [$profit['y'], $profit['height'], $profit['negative']]);
        $this->assertSame([75.0, 25.0, true], [$loss['y'], $loss['height'], $loss['negative']]);
    }

    public function testOnlyNegativeValues(): void
    {
        $chart = $this->createChart(['A' => -10, 'B' => -40]);

        $this->assertSame(0.0, $chart->getBaselineY());
        [$a, $b] = $chart->getBars();
        $this->assertSame([0.0, 25.0], [$a['y'], $a['height']]);
        $this->assertSame([0.0, 100.0], [$b['y'], $b['height']]);
    }

    public function testAllZeroValues(): void
    {
        $chart = $this->createChart(['A' => 0, 'B' => 0.0]);

        $this->assertSame(100.0, $chart->getBaselineY());
        foreach ($chart->getBars() as $bar) {
            $this->assertSame([100.0, 0.0], [$bar['y'], $bar['height']]);
        }
    }

    public function testTinyValuesHaveAMinimumVisibleHeight(): void
    {
        $chart = $this->createChart(['Big' => 100000, 'Tiny' => 1, 'Zero' => 0]);

        [, $tiny, $zero] = $chart->getBars();
        $this->assertSame([98.5, 1.5], [$tiny['y'], $tiny['height']]);
        $this->assertSame([100.0, 0.0], [$zero['y'], $zero['height']]);
    }

    public function testCoordinatesAreRoundedToTwoDecimals(): void
    {
        $chart = $this->createChart(['A' => 3, 'B' => 1]);

        $this->assertSame([66.67, 33.33], [$chart->getBars()[1]['y'], $chart->getBars()[1]['height']]);
    }

    public function testNumericLabelsAndValuesAreNormalized(): void
    {
        $chart = $this->createChart(['2023' => '1500', '2024' => '2250.5']);

        $this->assertSame(['2023' => 1500, '2024' => 2250.5], $chart->data);
        $bars = $chart->getBars();
        $this->assertSame(['2023', '2024'], array_column($bars, 'label'));
        $this->assertSame(['1,500', '2,250.5'], array_column($bars, 'formattedValue'));
    }

    public function testValuesAreFormattedWithTheCurrentLocale(): void
    {
        \Locale::setDefault('de');
        $chart = $this->createChart(['A' => 1234.5]);

        $this->assertSame('1.234,5', $chart->getBars()[0]['formattedValue']);
        $this->assertSame('1.234,5', $chart->getFormattedMaxValue());
    }

    public function testEmptyLabelIsTreatedAsNull(): void
    {
        $chart = new BarChart(new IntlFormatter());
        $chart->mount(['A' => 1], '');

        $this->assertNull($chart->ariaLabel);
    }

    #[DataProvider('provideInvalidValues')]
    public function testInvalidValuesThrow(mixed $value, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->createChart(['Jan' => 1, 'Feb' => $value]);
    }

    public static function provideInvalidValues(): iterable
    {
        yield 'non-numeric string' => ['many', 'The values of the "data" prop of the BarChart component must be numbers, but the value of "Feb" is of type "string".'];
        yield 'null' => [null, 'but the value of "Feb" is of type "null".'];
        yield 'boolean' => [true, 'but the value of "Feb" is of type "bool".'];
        yield 'array' => [[1], 'but the value of "Feb" is of type "array".'];
        yield 'infinite' => [\INF, 'must be finite numbers, but the value of "Feb" is "INF".'];
        yield 'not a number' => [\NAN, 'must be finite numbers, but the value of "Feb" is "NAN".'];
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function createChart(array $data): BarChart
    {
        $chart = new BarChart(new IntlFormatter());
        $chart->mount($data);

        return $chart;
    }
}
