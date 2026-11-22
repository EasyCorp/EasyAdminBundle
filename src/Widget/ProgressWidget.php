<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Displays the progress of a value towards a target (e.g. the sales of
 * this month compared to the sales goal).
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class ProgressWidget implements WidgetInterface
{
    use WidgetTrait;

    public const OPTION_VALUE = 'value';
    public const OPTION_TARGET = 'target';

    public static function new(TranslatableInterface|string|null $label = null): self
    {
        $widget = (new self())
            ->setLabel($label)
            ->setTemplateName('widget/progress')
            ->setDefaultColumns(3)
            ->setCustomOption(self::OPTION_VALUE, 0)
            ->setCustomOption(self::OPTION_TARGET, 100);

        $widget->setDataEntry('value', static fn (WidgetContext $context): int|float => self::resolveNumber($context, self::OPTION_VALUE));
        $widget->setDataEntry('target', static fn (WidgetContext $context): int|float => self::resolveNumber($context, self::OPTION_TARGET));

        return $widget;
    }

    /**
     * @param int|float|callable $value The current value or a closure that returns it
     */
    public function setValue(int|float|callable $value): self
    {
        $this->setCustomOption(self::OPTION_VALUE, self::normalizeNumberOrCallable($value, __METHOD__));

        return $this;
    }

    /**
     * @param int|float|callable $target The value that means 100% of progress or a closure that returns it
     */
    public function setTarget(int|float|callable $target): self
    {
        $this->setCustomOption(self::OPTION_TARGET, self::normalizeNumberOrCallable($target, __METHOD__));

        return $this;
    }

    private static function normalizeNumberOrCallable(int|float|callable $value, string $method): int|float|callable
    {
        // strings and arrays can be the name of any function or method (e.g. 'phpinfo'), so they are never called
        if (\is_string($value) || \is_array($value)) {
            throw new \InvalidArgumentException(sprintf('The "%s()" method only accepts numbers, closures and invokable objects (%s given).', $method, get_debug_type($value)));
        }

        if (\is_float($value) && !is_finite($value)) {
            throw new \InvalidArgumentException(sprintf('The "%s()" method only accepts finite numbers.', $method));
        }

        return $value;
    }

    private static function resolveNumber(WidgetContext $context, string $optionName): int|float
    {
        $number = $context->resolve($context->getWidget()->getCustomOption($optionName));

        // database queries often return numbers as strings (e.g. the result of SUM())
        if (\is_string($number) && is_numeric($number)) {
            $number += 0;
        }

        if ((!\is_int($number) && !\is_float($number)) || (\is_float($number) && !is_finite($number))) {
            throw new \InvalidArgumentException(sprintf('The %s of the "%s" progress widget must be a finite number (%s given).', $optionName, $context->getWidget()->getName() ?? $context->getWidget()->getType(), get_debug_type($number)));
        }

        return $number;
    }
}
