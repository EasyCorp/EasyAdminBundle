<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Twig\Component;

use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;

/**
 * Renders a key metric: a label, a big value, its change compared to a previous
 * period and a short description.
 */
class Stat
{
    /**
     * The text displayed above the value (e.g. 'Revenue').
     */
    public ?string $label = null;
    /**
     * The main value: integers and floats are formatted for the current locale; strings and objects are displayed as they are.
     */
    public int|float|string|\Stringable|null $value = null;
    /**
     * A short text displayed under the value (e.g. 'Compared to the previous month').
     */
    public ?string $description = null;
    /**
     * The percentage change of the value (e.g. 8.2 means +8.2%); null hides it.
     */
    public int|float|null $delta = null;
    /**
     * Whether a positive delta is good news (displayed as a success) or bad news (displayed as a danger), e.g. false for costs or errors.
     */
    public bool $increaseIsGood = true;

    public function __construct(
        private readonly IntlFormatter $intlFormatter,
    ) {
    }

    public function mount(?string $label = null, int|float|string|\Stringable|null $value = null, ?string $description = null, int|float|string|null $delta = null, bool $increaseIsGood = true): void
    {
        // templates often pass props as strings (e.g. label="{{ ... }}"), so empty strings mean "no value"
        $this->label = '' === $label ? null : $label;
        $this->value = '' === $value ? null : $value;
        $this->description = '' === $description ? null : $description;
        $this->delta = $this->normalizeDelta($delta);
        $this->increaseIsGood = $increaseIsGood;
    }

    public function getFormattedValue(): string|\Stringable|null
    {
        if (\is_int($this->value) || \is_float($this->value)) {
            return $this->intlFormatter->formatNumber($this->value);
        }

        return $this->value;
    }

    public function getFormattedDelta(): ?string
    {
        if (null === $roundedDelta = $this->getRoundedDelta()) {
            return null;
        }

        $sign = match (true) {
            $roundedDelta > 0 => '+',
            $roundedDelta < 0 => '-',
            default => '',
        };

        return $sign.$this->intlFormatter->formatNumber(abs($roundedDelta) / 100, ['max_fraction_digit' => 1], 'percent');
    }

    /**
     * Returns 'increase', 'decrease' or 'no_change' (or null when there's no delta).
     */
    public function getDeltaDirection(): ?string
    {
        if (null === $roundedDelta = $this->getRoundedDelta()) {
            return null;
        }

        return match (true) {
            $roundedDelta > 0 => 'increase',
            $roundedDelta < 0 => 'decrease',
            default => 'no_change',
        };
    }

    public function getDeltaCssClass(): ?string
    {
        return match ($this->getDeltaDirection()) {
            null => null,
            'no_change' => 'ea-stat-delta-neutral',
            'increase' => $this->increaseIsGood ? 'ea-stat-delta-good' : 'ea-stat-delta-bad',
            default => $this->increaseIsGood ? 'ea-stat-delta-bad' : 'ea-stat-delta-good',
        };
    }

    // the direction is decided after rounding, so a tiny change displayed as "0%" is not shown as an increase or decrease
    private function getRoundedDelta(): ?float
    {
        return null === $this->delta ? null : round($this->delta, 1);
    }

    private function normalizeDelta(int|float|string|null $delta): int|float|null
    {
        if (null === $delta || '' === $delta) {
            return null;
        }

        if (\is_string($delta)) {
            if (!is_numeric($delta)) {
                throw new \InvalidArgumentException(sprintf('The "delta" prop of the Stat component must be a number (e.g. 8.2 for a +8.2%% change), but "%s" was given.', $delta));
            }

            return (float) $delta;
        }

        if (!is_finite((float) $delta)) {
            throw new \InvalidArgumentException('The "delta" prop of the Stat component must be a finite number.');
        }

        return $delta;
    }
}
