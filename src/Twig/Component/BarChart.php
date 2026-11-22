<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;

/**
 * Renders a vertical bar chart of a single series of values as an SVG image,
 * with a hidden data table for screen readers.
 */
class BarChart
{
    // the geometry of the bars is expressed in percentages of the plot, so the chart fills any
    // width and height without stretching the SVG (which would deform the rounded corners)
    private const PLOT_HEIGHT = 100;
    // the percentage of the slot of each bar that is left empty between bars
    private const BAR_GAP = 20;
    // non-zero values that are tiny compared to the others would be invisible otherwise
    private const MIN_BAR_HEIGHT = 1.5;

    /**
     * The values of the chart as label => value pairs (e.g. ['Jan' => 120, 'Feb' => 95]); the bars are displayed in the same order.
     *
     * @var array<int|string, int|float>
     */
    public array $data = [];
    /**
     * The accessible name of the chart and the caption of the hidden data table, which is what screen readers read instead of the chart.
     */
    public ?string $ariaLabel = null;

    /** @var list<array{label: string, value: int|float}> */
    private array $points = [];

    public function __construct(
        private readonly IntlFormatter $intlFormatter,
    ) {
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public function mount(array $data = [], ?string $ariaLabel = null): void
    {
        $this->data = [];
        $this->points = [];
        foreach ($data as $pointLabel => $value) {
            // PHP turns numeric string keys (e.g. years like '2024') into integers
            $pointLabel = (string) $pointLabel;
            $value = $this->normalizeValue($pointLabel, $value);

            $this->data[$pointLabel] = $value;
            $this->points[] = ['label' => $pointLabel, 'value' => $value];
        }

        $this->ariaLabel = '' === $ariaLabel ? null : $ariaLabel;
    }

    public function isEmpty(): bool
    {
        return [] === $this->points;
    }

    public function getMaxValue(): int|float
    {
        return max([0, ...array_column($this->points, 'value')]);
    }

    public function getMinValue(): int|float
    {
        return min([0, ...array_column($this->points, 'value')]);
    }

    public function getFormattedMaxValue(): string
    {
        return $this->intlFormatter->formatNumber($this->getMaxValue());
    }

    public function getFormattedMinValue(): string
    {
        return $this->intlFormatter->formatNumber($this->getMinValue());
    }

    public function hasNegativeValues(): bool
    {
        return $this->getMinValue() < 0;
    }

    /**
     * The vertical position of the zero value as a percentage of the height of the plot (0 is the top).
     */
    public function getBaselineY(): float
    {
        $range = $this->getRange();
        if (0.0 === $range) {
            return (float) self::PLOT_HEIGHT;
        }

        return $this->roundCoordinate($this->getMaxValue() / $range * self::PLOT_HEIGHT);
    }

    /**
     * The position and size of each bar are percentages of the width and height of the plot.
     *
     * @return list<array{label: string, value: int|float, formattedValue: string, x: float, y: float, width: float, height: float, negative: bool}>
     */
    public function getBars(): array
    {
        $range = $this->getRange();
        $baselineY = $this->getBaselineY();
        $slotWidth = 100 / max(1, \count($this->points));

        $bars = [];
        foreach ($this->points as $position => $point) {
            $value = $point['value'];
            $height = 0.0 === $range ? 0.0 : abs($value) / $range * self::PLOT_HEIGHT;
            if (0.0 !== (float) $value && $height < self::MIN_BAR_HEIGHT) {
                $height = self::MIN_BAR_HEIGHT;
            }

            if ($value >= 0) {
                $y = max(0.0, $baselineY - $height);
                $height = min($height, $baselineY);
            } else {
                $y = $baselineY;
                $height = min($height, self::PLOT_HEIGHT - $baselineY);
            }

            $bars[] = [
                'label' => $point['label'],
                'value' => $value,
                'formattedValue' => $this->intlFormatter->formatNumber($value),
                'x' => $this->roundCoordinate(($position + self::BAR_GAP / 200) * $slotWidth),
                'y' => $this->roundCoordinate($y),
                'width' => $this->roundCoordinate($slotWidth * (100 - self::BAR_GAP) / 100),
                'height' => $this->roundCoordinate($height),
                'negative' => $value < 0,
            ];
        }

        return $bars;
    }

    private function getRange(): float
    {
        return (float) ($this->getMaxValue() - $this->getMinValue());
    }

    private function roundCoordinate(float $coordinate): float
    {
        $rounded = round($coordinate, 2);

        // avoids printing '-0' in the SVG attributes
        return 0.0 === $rounded ? 0.0 : $rounded;
    }

    private function normalizeValue(string $label, mixed $value): int|float
    {
        // database queries often return numbers as strings (e.g. the result of SUM())
        if (\is_string($value) && is_numeric($value)) {
            $value += 0;
        }

        if (!\is_int($value) && !\is_float($value)) {
            throw new \InvalidArgumentException(sprintf('The values of the "data" prop of the BarChart component must be numbers, but the value of "%s" is of type "%s".', $label, get_debug_type($value)));
        }

        if (!is_finite($value)) {
            throw new \InvalidArgumentException(sprintf('The values of the "data" prop of the BarChart component must be finite numbers, but the value of "%s" is "%s".', $label, is_nan($value) ? 'NAN' : ($value > 0 ? 'INF' : '-INF')));
        }

        return $value;
    }
}
