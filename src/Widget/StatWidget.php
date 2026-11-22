<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Widget;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Widget\WidgetInterface;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Displays a single value (e.g. the number of users) with an optional
 * description and the percentage change compared to a previous period.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class StatWidget implements WidgetInterface
{
    use WidgetTrait;

    public const OPTION_VALUE = 'value';
    public const OPTION_VALUE_FORMATTER = 'valueFormatter';
    public const OPTION_DESCRIPTION = 'description';
    public const OPTION_DELTA = 'delta';
    public const OPTION_INCREASE_IS_GOOD = 'increaseIsGood';

    public static function new(TranslatableInterface|string|null $label = null): self
    {
        $widget = (new self())
            ->setLabel($label)
            ->setTemplateName('widget/stat')
            ->setDefaultColumns(3)
            ->setCustomOption(self::OPTION_VALUE, null)
            ->setCustomOption(self::OPTION_VALUE_FORMATTER, null)
            ->setCustomOption(self::OPTION_DESCRIPTION, null)
            ->setCustomOption(self::OPTION_DELTA, null)
            ->setCustomOption(self::OPTION_INCREASE_IS_GOOD, true);

        $widget->setDataEntry('value', static function (WidgetContext $context): mixed {
            $widget = $context->getWidget();
            $value = $context->resolve($widget->getCustomOption(self::OPTION_VALUE));

            // the formatter receives the value as it is, so it can format any kind of object (e.g. dates or money)
            if (null !== $formatter = $widget->getCustomOption(self::OPTION_VALUE_FORMATTER)) {
                $formattedValue = $formatter($value, $context);
                if (!\is_string($formattedValue) && !$formattedValue instanceof \Stringable) {
                    throw new \InvalidArgumentException(sprintf('The callable passed to the "formatValue()" method of the "%s" stat widget must return a string or a Stringable object (%s returned).', self::getNameForErrors($context), get_debug_type($formattedValue)));
                }

                return $formattedValue;
            }

            if (null !== $value && !\is_int($value) && !\is_float($value) && !\is_string($value) && !$value instanceof \Stringable) {
                throw new \InvalidArgumentException(sprintf('The value of the "%s" stat widget must be an integer, a float, a string, a Stringable object or null (%s given). Use the "formatValue()" method to display other values.', self::getNameForErrors($context), get_debug_type($value)));
            }

            return $value;
        });

        $widget->setDataEntry('delta', static function (WidgetContext $context): int|float|null {
            $delta = $context->resolve($context->getWidget()->getCustomOption(self::OPTION_DELTA));

            // database queries often return numbers as strings (e.g. the result of SUM())
            if (\is_string($delta) && is_numeric($delta)) {
                $delta += 0;
            }

            if (null !== $delta && !\is_int($delta) && !\is_float($delta)) {
                throw new \InvalidArgumentException(sprintf('The delta of the "%s" stat widget must be an integer, a float or null (%s given).', self::getNameForErrors($context), get_debug_type($delta)));
            }

            if (\is_float($delta) && !is_finite($delta)) {
                throw new \InvalidArgumentException(sprintf('The delta of the "%s" stat widget must be a finite number.', self::getNameForErrors($context)));
            }

            return $delta;
        });

        return $widget;
    }

    /**
     * @param mixed $value A scalar value, a Stringable object or a closure that returns any of them
     */
    public function setValue(mixed $value): self
    {
        $this->setCustomOption(self::OPTION_VALUE, $value);

        return $this;
    }

    public function setDescription(TranslatableInterface|string $description): self
    {
        $this->setCustomOption(self::OPTION_DESCRIPTION, $description);

        return $this;
    }

    /**
     * @param int|float|callable $delta          The percentage change of the value (e.g. 8.2 means +8.2%) or a closure that returns it (or null)
     * @param bool               $increaseIsGood Set it to false when a lower value is better (e.g. response times)
     */
    public function setDelta(int|float|callable $delta, bool $increaseIsGood = true): self
    {
        if (\is_callable($delta)) {
            $delta = self::normalizeCallable($delta, __METHOD__);
        }

        $this->setCustomOption(self::OPTION_DELTA, $delta);
        $this->setCustomOption(self::OPTION_INCREASE_IS_GOOD, $increaseIsGood);

        return $this;
    }

    /**
     * @param callable|null $callable Called as fn (mixed $value, WidgetContext $context): string|\Stringable;
     *                                the result is escaped when rendered unless it's a Twig\Markup object
     */
    public function formatValue(?callable $callable): self
    {
        $this->setCustomOption(self::OPTION_VALUE_FORMATTER, null === $callable ? null : self::normalizeCallable($callable, __METHOD__));

        return $this;
    }

    private static function normalizeCallable(callable $callable, string $method): callable
    {
        // strings and arrays can be the name of any function or method (e.g. 'phpinfo'), so they are never called
        if (\is_string($callable) || \is_array($callable)) {
            throw new \InvalidArgumentException(sprintf('The "%s()" method only accepts closures and invokable objects as callables (%s given).', $method, get_debug_type($callable)));
        }

        return $callable;
    }

    private static function getNameForErrors(WidgetContext $context): string
    {
        return $context->getWidget()->getName() ?? $context->getWidget()->getType();
    }
}
